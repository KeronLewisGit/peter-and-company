<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AccountStatus;
use App\Enums\PaymentTerms;
use App\Enums\TradeType;
use App\Http\Controllers\Controller;
use App\Models\TradeAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Approved trade customers. Everyone on the trade desk can look one up; only credit control changes one. */
class TradeAccountController extends Controller
{
    public function index(Request $request): View
    {
        $q = input_string($request->query('q'));
        $status = AccountStatus::tryFrom(input_string($request->query('status')));

        return view('admin.accounts.index', [
            'accounts' => TradeAccount::query()->withCount('leads')
                ->when($status, fn (Builder $b, AccountStatus $s) => $b->where('status', $s))
                ->when($q !== '', function (Builder $b) use ($q) {
                    $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
                    $b->where(fn (Builder $w) => $w->where('business_name', 'like', $like)->orWhere('number', 'like', $like)->orWhere('contact_name', 'like', $like));
                })
                ->orderBy('business_name')->paginate(30)->withQueryString(),
            'filters' => ['q' => $q, 'status' => $status?->value],
            'canEdit' => $this->user($request)->can('manage-accounts'),
        ]);
    }

    /** An existing customer who never applied online (e.g. moved over from the old customer list). */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:160'],
            'trade_type' => ['required', Rule::enum(TradeType::class)],
            'contact_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'district' => ['nullable', Rule::in(array_keys((array) peterco('districts')))],
            'terms' => ['required', Rule::enum(PaymentTerms::class)],
            'pharmacy_licence' => ['nullable', 'string', 'max:60'],
        ]);
        $account = TradeAccount::create($data + ['status' => AccountStatus::Active, 'approved_by' => $this->user($request)->id, 'approved_at' => now()]);

        return back()->with('status', "Account {$account->number} opened for {$account->business_name}.");
    }

    public function update(Request $request, TradeAccount $account): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(AccountStatus::class)],
            'terms' => ['required', Rule::enum(PaymentTerms::class)],
        ]);
        $account->update($data);

        return back()->with('status', "{$account->number} saved.");
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return $user;
    }
}
