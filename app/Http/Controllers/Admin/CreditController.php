<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Enums\PaymentTerms;
use App\Enums\TradeType;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\TradeAccount;
use App\Models\User;
use App\Notifications\AccountOpened;
use App\Support\Notify;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Credit control: trade account applications by stage, and approval, which opens the account. */
class CreditController extends Controller
{
    public function index(): View
    {
        $leads = Lead::where('type', LeadType::Account)->with('assignee')
            ->where(fn ($q) => $q->whereNotIn('status', [LeadStatus::Approved, LeadStatus::Declined])->orWhere('status_changed_at', '>=', now()->subDays(60)))
            ->oldest()->get();

        return view('admin.credit.index', [
            'columns' => collect(LeadType::Account->statuses())->map(fn (LeadStatus $s) => ['status' => $s, 'leads' => $leads->where('status', $s)->values()]),
        ]);
    }

    public function approve(Request $request, Lead $lead): RedirectResponse
    {
        Gate::authorize('decideCredit', $lead);
        abort_if($lead->status === LeadStatus::Approved || $lead->trade_account_id !== null, 409, 'This application already has an account.');
        $data = $request->validate([
            'terms' => ['required', Rule::enum(PaymentTerms::class)],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        /** @var User $user */
        $user = $request->user();
        $type = TradeType::tryFrom((string) $lead->detail('trade_type')) ?? TradeType::Other;

        $account = DB::transaction(function () use ($lead, $data, $user, $type) {
            $account = TradeAccount::create([
                'business_name' => $lead->business_name ?? $lead->name,
                'trade_type' => $type,
                'contact_name' => $lead->name,
                'phone' => $lead->phone,
                'email' => $lead->email,
                'district' => $lead->detail('district'),
                'terms' => $data['terms'],
                'status' => AccountStatus::Active,
                'pharmacy_licence' => $type->isMedical() ? $lead->detail('pharmacy_licence_no') : null,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);
            $lead->trade_account_id = $account->id;
            $lead->moveTo(LeadStatus::Approved);
            $lead->save();
            $lead->notes()->create(['user_id' => $user->id, 'kind' => 'credit', 'body' => "Approved. Trade account {$account->number} opened on ".PaymentTerms::from($data['terms'])->short().' terms.'.(! empty($data['note']) ? ' '.$data['note'] : '')]);

            return $account;
        });

        Notify::customer($account->email, new AccountOpened($account));

        return to_route('admin.leads.show', $lead)->with('status', "Account {$account->number} opened for {$account->business_name}. The customer has been emailed the number.");
    }
}
