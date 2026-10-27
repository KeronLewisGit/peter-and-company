<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadFile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** The unified inbox: every web request the signed-in role works, new first. */
class LeadController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->user($request);
        $filters = $this->filters($request, $user);
        $base = Lead::visibleTo($user);

        $counts = (clone $base)->filter(array_merge($filters, ['status' => null]))
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $type = $filters['type'] ? LeadType::from($filters['type']) : null;

        return view('admin.leads.index', [
            'leads' => (clone $base)->with(['assignee', 'vacancy', 'tradeAccount'])->filter($filters)->inboxOrder()
                ->paginate(25)->withQueryString(),
            'filters' => $filters,
            'counts' => $counts,
            'openCount' => (clone $base)->filter(array_merge($filters, ['status' => null]))->open()->count(),
            'statusTabs' => $type ? $type->statuses() : [LeadStatus::New],
            'types' => $user->role->leadTypes(),
            'staff' => User::active()->orderBy('name')->get(['id', 'name', 'role']),
        ]);
    }

    public function show(Request $request, Lead $lead): View
    {
        Gate::authorize('view', $lead);
        $lead->load(['assignee', 'notes.user', 'files', 'vacancy', 'tradeAccount']);
        $user = $this->user($request);
        // Previous / next in the same order as the inbox.
        $ids = Lead::visibleTo($user)->inboxOrder()->pluck('id');
        $pos = $ids->search($lead->id);

        return view('admin.leads.show', [
            'lead' => $lead,
            'staff' => User::active()->orderBy('name')->get(['id', 'name', 'role'])
                ->filter(fn (User $u) => $u->canWork($lead->type))->values(),
            'canSeeCredit' => $user->can('viewCreditDetails', $lead),
            'canDecide' => $user->can('decideCredit', $lead),
            'neighbours' => [
                'prev' => $pos !== false ? $ids->get($pos - 1) : null,
                'next' => $pos !== false ? $ids->get($pos + 1) : null,
            ],
        ]);
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        Gate::authorize('update', $lead);
        $user = $this->user($request);
        $data = $request->validate([
            'status' => ['sometimes', Rule::in(array_map(fn (LeadStatus $s) => $s->value, $lead->type->statuses()))],
            'assigned_to' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')->where('active', true)],
            'delivery_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ]);

        if (isset($data['status']) && ($status = LeadStatus::from($data['status'])) !== $lead->status) {
            if ($lead->type === LeadType::Account) {
                // Only credit control decides on applications, and approval opens the account.
                if (! $user->can('decideCredit', $lead)) {
                    return back()->withErrors(['status' => 'Only credit control can change the stage of a trade account application.']);
                }
                if ($status === LeadStatus::Approved) {
                    return back()->withErrors(['status' => 'Use "Approve and open account" to approve an application.']);
                }
                if ($lead->status === LeadStatus::Approved) {
                    return back()->withErrors(['status' => 'This application is approved and its account is open. Put the account on hold instead.']);
                }
            }
            if ($lead->moveTo($status)) {
                $lead->notes()->create(['user_id' => $user->id, 'kind' => 'status', 'body' => 'Status changed to '.$status->label().'.']);
            }
        }
        // The form posts the id as a string; compare as integers so an unchanged choice adds no note.
        $assignTo = isset($data['assigned_to']) ? (int) $data['assigned_to'] : null;
        if (array_key_exists('assigned_to', $data) && $lead->assigned_to !== $assignTo) {
            $assignee = $assignTo ? User::find($assignTo) : null;
            if ($assignee && ! $assignee->canWork($lead->type)) {
                return back()->withErrors(['assigned_to' => "{$assignee->name} works in {$assignee->role->label()} and can't see this request."]);
            }
            $lead->assigned_to = $assignee?->id;
            $lead->notes()->create(['user_id' => $user->id, 'kind' => 'assign', 'body' => $assignee ? "Assigned to {$assignee->name}." : 'Unassigned.']);
        }
        if (array_key_exists('delivery_date', $data) && $lead->type === LeadType::Order) {
            $old = $lead->delivery_date?->toDateString();
            $lead->delivery_date = $data['delivery_date'] ? Carbon::parse($data['delivery_date']) : null;
            if ($old !== $lead->delivery_date?->toDateString()) {
                $lead->notes()->create(['user_id' => $user->id, 'kind' => 'status', 'body' => 'Delivery day set to '.($lead->delivery_date?->format('D j M') ?? 'no date').'.']);
            }
        }
        $lead->save();

        return back()->with('status', "{$lead->reference} saved.");
    }

    public function note(Request $request, Lead $lead): RedirectResponse
    {
        Gate::authorize('update', $lead);
        $data = $request->validate(['body' => ['required', 'string', 'max:3000'], 'contacted' => ['nullable', 'boolean']]);
        $user = $this->user($request);

        $lead->notes()->create(['user_id' => $user->id, 'kind' => 'note', 'body' => $data['body']]);
        // Logging a call on a new request moves it to its first working stage.
        if (! empty($data['contacted']) && $lead->status === LeadStatus::New && $lead->type !== LeadType::Account) {
            $next = $lead->type->statuses()[1];
            $lead->moveTo($next);
            $lead->notes()->create(['user_id' => $user->id, 'kind' => 'status', 'body' => 'Status changed to '.$next->label().'.']);
        }
        if (! empty($data['contacted'])) {
            $lead->first_contacted_at ??= now();
        }
        $lead->save();

        return back()->with('status', 'Note added.')->withFragment('timeline');
    }

    /** Uploads stream from the private disk. Credit documents: credit control only. CVs: HR and admin. */
    public function file(Request $request, Lead $lead, LeadFile $file): StreamedResponse
    {
        Gate::authorize('openFile', [$lead, $file]);
        abort_unless(Storage::disk('local')->exists($file->path), 404);

        $lead->notes()->create(['user_id' => $this->user($request)->id, 'kind' => 'system', 'body' => "Opened {$file->label()} ({$file->original_name})."]);
        $name = Str::slug($lead->reference.'-'.$file->label()).'.'.pathinfo($file->path, PATHINFO_EXTENSION);

        return $file->isImage() && ! $request->boolean('download')
            ? Storage::disk('local')->response($file->path, $name, ['Cache-Control' => 'private, no-store'])
            : Storage::disk('local')->download($file->path, $name, ['Cache-Control' => 'private, no-store']);
    }

    public function export(Request $request): StreamedResponse
    {
        $user = $this->user($request);
        $query = Lead::visibleTo($user)->with(['assignee', 'vacancy', 'tradeAccount'])->filter($this->filters($request, $user));

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // byte-order mark so Excel reads accents correctly
            fputcsv($out, ['Reference', 'Received', 'Type', 'Status', 'Name', 'Business', 'Phone', 'Email', 'Prefers', 'About', 'Trade account', 'Delivery requested', 'Assigned to', 'First contact', 'Minutes to first contact'], escape: '');
            foreach ($query->lazyByIdDesc(500) as $l) {
                /** @var Lead $l */
                fputcsv($out, array_map(self::csvSafe(...), [
                    $l->reference, $l->created_at->format('Y-m-d H:i'), $l->type->label(), $l->status->label(), $l->name, $l->business_name,
                    format_phone($l->phone), $l->email, $l->contact_pref, $l->subject(), $l->tradeAccount->number ?? '',
                    $l->delivery_date?->format('Y-m-d'), $l->assignee->name ?? '', $l->first_contacted_at?->format('Y-m-d H:i'),
                    $l->first_contacted_at ? (string) (int) $l->created_at->diffInMinutes($l->first_contacted_at) : '',
                ]), escape: '');
            }
            fclose($out);
        }, 'peterco-requests-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Customer-typed text starting with = + - @ would run as a formula in Excel, so it gets a leading quote. */
    private static function csvSafe(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }

    /** @return array<string, string|null> */
    private function filters(Request $request, User $user): array
    {
        $type = LeadType::tryFrom(input_string($request->query('type')));
        $statusIn = input_string($request->query('status'));
        $assigned = input_string($request->query('assigned'));

        return [
            'type' => $type && $user->canWork($type) ? $type->value : null,
            'status' => $statusIn === 'open' ? 'open' : LeadStatus::tryFrom($statusIn)?->value,
            'assigned' => match (true) {
                $assigned === 'me' => (string) $user->id,
                $assigned === 'none', ctype_digit($assigned) => $assigned,
                default => null,
            },
            'overdue' => $request->boolean('overdue') ? '1' : null,
            'q' => input_string($request->query('q')) ?: null,
        ];
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
