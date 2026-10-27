<x-layouts.admin title="Trade accounts">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div class="flex-1"><label for="aq" class="mb-1 block text-xs font-semibold text-ink-3">Search</label><input id="aq" name="q" value="{{ $filters['q'] }}" class="a-input w-full min-w-48" placeholder="Business, contact or PC- number"></div>
        <div><label for="ast" class="mb-1 block text-xs font-semibold text-ink-3">Status</label>
            <select id="ast" name="status" class="a-input"><option value="">Any</option>@foreach (\App\Enums\AccountStatus::cases() as $s)<option value="{{ $s->value }}" @selected($filters['status'] === $s->value)>{{ $s->label() }}</option>@endforeach</select></div>
        <button class="a-btn a-btn-primary">Filter</button>
        @if (array_filter($filters))<a href="{{ route('admin.accounts.index') }}" class="a-btn a-btn-ghost">Clear</a>@endif
    </form>

    <div class="a-card table-box mt-5">
        <table class="data-table">
            <thead><tr><th>Account</th><th>Business</th><th>Type</th><th>District</th><th class="r">Requests</th><th>Terms and status</th></tr></thead>
            <tbody>
                @forelse ($accounts as $a)
                    <tr>
                        <td class="num whitespace-nowrap font-semibold">{{ $a->number }}@if($a->is_demo)<br><span class="sample-badge">Demo</span>@endif</td>
                        <td><p class="font-semibold">{{ $a->business_name }}</p><p class="text-sm text-ink-3">{{ $a->contact_name }} · {{ format_phone($a->phone) }}</p></td>
                        <td class="whitespace-nowrap">{{ $a->trade_type->label() }}@if($a->pharmacy_licence)<br><span class="text-xs text-ink-3">Licence {{ $a->pharmacy_licence }}</span>@endif</td>
                        <td class="whitespace-nowrap">{{ peterco('districts.'.$a->district) ?? '—' }}</td>
                        <td class="r">{{ $a->leads_count }}</td>
                        <td>
                            @if ($canEdit)
                                <form method="POST" action="{{ route('admin.accounts.update', $a) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf @method('PATCH')
                                    <label class="sr-only" for="t-{{ $a->id }}">Terms</label>
                                    <select id="t-{{ $a->id }}" name="terms" class="a-input">@foreach (\App\Enums\PaymentTerms::cases() as $t)<option value="{{ $t->value }}" @selected($a->terms === $t)>{{ $t->short() }}</option>@endforeach</select>
                                    <label class="sr-only" for="s-{{ $a->id }}">Status</label>
                                    <select id="s-{{ $a->id }}" name="status" class="a-input">@foreach (\App\Enums\AccountStatus::cases() as $s)<option value="{{ $s->value }}" @selected($a->status === $s)>{{ $s->label() }}</option>@endforeach</select>
                                    <button class="a-btn a-btn-ghost">Save</button>
                                </form>
                            @else
                                <span class="whitespace-nowrap">{{ $a->terms->short() }} · </span><x-chip :tone="$a->status->tone()" :icon="$a->status->icon()">{{ $a->status->label() }}</x-chip>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-ink-3">No accounts match.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $accounts->links() }}</div>

    @if ($canEdit)
        <details class="a-card mt-6 p-5">
            <summary class="cursor-pointer font-semibold">Add an existing customer</summary>
            <p class="mt-1 text-sm text-ink-3">For customers already on credit control's books who never applied online.</p>
            <form method="POST" action="{{ route('admin.accounts.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @csrf
                <div><label class="mb-1 block text-sm font-semibold" for="n-bn">Business</label><input id="n-bn" name="business_name" class="a-input w-full" required maxlength="160"></div>
                <div><label class="mb-1 block text-sm font-semibold" for="n-tt">Type</label><select id="n-tt" name="trade_type" class="a-input w-full">@foreach (\App\Enums\TradeType::options() as $v => $t)<option value="{{ $v }}">{{ $t }}</option>@endforeach</select></div>
                <div><label class="mb-1 block text-sm font-semibold" for="n-cn">Contact</label><input id="n-cn" name="contact_name" class="a-input w-full" required maxlength="120"></div>
                <div><label class="mb-1 block text-sm font-semibold" for="n-ph">Phone</label><input id="n-ph" name="phone" type="tel" class="a-input w-full" required maxlength="30"></div>
                <div><label class="mb-1 block text-sm font-semibold" for="n-em">Email</label><input id="n-em" name="email" type="email" class="a-input w-full" maxlength="255"></div>
                <div><label class="mb-1 block text-sm font-semibold" for="n-di">District</label><select id="n-di" name="district" class="a-input w-full"><option value="">—</option>@foreach (peterco('districts') as $v => $t)<option value="{{ $v }}">{{ $t }}</option>@endforeach</select></div>
                <div><label class="mb-1 block text-sm font-semibold" for="n-te">Terms</label><select id="n-te" name="terms" class="a-input w-full">@foreach (\App\Enums\PaymentTerms::cases() as $t)<option value="{{ $t->value }}">{{ $t->label() }}</option>@endforeach</select></div>
                <div><label class="mb-1 block text-sm font-semibold" for="n-pl">Pharmacy licence</label><input id="n-pl" name="pharmacy_licence" class="a-input w-full" maxlength="60"></div>
                <div class="sm:col-span-2 lg:col-span-4"><button class="a-btn a-btn-primary">Open account</button></div>
            </form>
        </details>
    @endif
</x-layouts.admin>
