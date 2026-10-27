@use('App\Enums\LeadType')
@php
    /** @var \App\Models\Lead $lead */
    $d = $lead->details ?? [];
    $isAccount = $lead->type === LeadType::Account;
    $districts = (array) peterco('districts');
    $rows = match ($lead->type) {
        LeadType::Order => [
            'Business' => $lead->business_name,
            'Type of business' => \App\Enums\TradeType::tryFrom((string) ($d['trade_type'] ?? ''))?->label(),
            'Trade account' => $lead->tradeAccount ? $lead->tradeAccount->number.' · '.$lead->tradeAccount->terms->label().' · '.$lead->tradeAccount->status->label() : 'None quoted',
            'District' => $districts[$d['district'] ?? ''] ?? null,
            'Deliver to' => $d['delivery_address'] ?? null,
            'Preferred day' => $lead->delivery_date?->format('l j F') ?? 'No preference',
            'Pharmaceuticals' => ! empty($d['pharmaceutical']) ? 'Yes, approved licensed account' : null,
        ],
        LeadType::Principal => [
            'Company' => $lead->business_name, 'Job title' => $d['job_title'] ?? null, 'Country' => $d['country'] ?? null,
            'Website' => $d['company_site'] ?? null, 'Brands' => $d['brands'] ?? null,
            'Categories' => collect($d['categories'] ?? [])->map(fn ($c) => peterco('divisions.'.$c.'.short', $c))->join(', '),
            'Sold in the Caribbean' => $d['caribbean'] ?? null,
        ],
        LeadType::Application => [
            'Vacancy' => $lead->vacancy?->title ?? 'General application',
            'Kind of work' => $d['role_interest'] ?? null,
            'Lives in' => $districts[$d['district'] ?? ''] ?? null,
            'Can start' => peterco('availability.'.($d['available'] ?? '')),
            'Driving licence' => ['none' => 'None', 'car' => 'Car', 'commercial' => 'Commercial vehicles'][$d['driving_licence'] ?? ''] ?? null,
        ],
        LeadType::Enquiry => ['Business' => $lead->business_name, 'Topic' => peterco('enquiry_topics.'.($d['topic'] ?? ''))],
        LeadType::Account => ['Business' => $lead->business_name, 'Type of business' => $d['trade_type_label'] ?? null, 'District' => $districts[$d['district'] ?? ''] ?? null],
    };
    $rows = array_filter($rows, fn ($v) => $v !== null && $v !== '');
    $waText = 'Hello '.strtok($lead->name, ' ').', this is Acado St. Lucia about your '.strtolower($lead->type->label()).' ('.$lead->reference.').';
    $lines = $lead->lines();
@endphp
<x-layouts.admin :title="$lead->business_name ?? $lead->name">
    <x-slot:actions>
        @if ($neighbours['prev'])<a href="{{ route('admin.leads.show', $neighbours['prev']) }}" class="a-btn a-btn-ghost" aria-label="Previous request in the inbox"><x-icon name="chevron-left" class="h-4 w-4" /></a>@endif
        @if ($neighbours['next'])<a href="{{ route('admin.leads.show', $neighbours['next']) }}" class="a-btn a-btn-ghost" aria-label="Next request in the inbox"><x-icon name="chevron-right" class="h-4 w-4" /></a>@endif
        <a href="{{ route('admin.leads.index') }}" class="a-btn a-btn-ghost">Back to inbox</a>
    </x-slot:actions>

    <div class="flex flex-wrap items-center gap-2 text-sm">
        <span class="chip num" data-tone="neutral"><x-icon :name="$lead->type->icon()" />{{ $lead->type->label() }} · {{ $lead->reference }}</span>
        <x-status :status="$lead->status" />
        @if ($lead->isOverdue())<span class="chip" data-tone="critical"><x-icon name="clock" />Waiting {{ $lead->created_at->diffForHumans(null, true) }} (target {{ $lead->type->targetLabel() }})</span>@endif
        <span class="text-ink-3">Received {{ $lead->created_at->format('D j M Y, H:i') }}</span>
    </div>

    <div class="mt-5 grid gap-6 xl:grid-cols-[1.4fr_1fr]">
        <div class="min-w-0 space-y-6">
            <section class="a-card p-5" aria-labelledby="contact-title">
                <h2 id="contact-title" class="font-semibold">Contact</h2>
                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <div class="min-w-0">
                        <p class="font-semibold">{{ $lead->name }}@if(! empty($d['position'])) <span class="font-normal text-ink-3">· {{ $d['position'] }}</span>@endif</p>
                        <p class="text-lg font-semibold num">{{ format_phone($lead->phone) }}</p>
                        <p class="truncate text-ink-2">{{ $lead->email }}</p>
                        <p class="mt-1 text-sm text-ink-3">Prefers {{ ['phone' => 'a phone call', 'whatsapp' => 'WhatsApp', 'email' => 'email'][$lead->contact_pref] ?? $lead->contact_pref }}</p>
                    </div>
                    <div class="flex flex-wrap content-start gap-2">
                        <a href="{{ tel_href($lead->phone) }}" class="a-btn a-btn-primary"><x-icon name="phone" class="h-4 w-4" />Call</a>
                        <a href="{{ wa_href($lead->phone, $waText) }}" class="a-btn a-btn-ghost" target="_blank" rel="noopener"><x-icon name="whatsapp" class="h-4 w-4" />WhatsApp</a>
                        @if ($lead->email)<a href="mailto:{{ $lead->email }}?subject={{ rawurlencode('Acado St. Lucia: '.$lead->reference) }}" class="a-btn a-btn-ghost"><x-icon name="mail" class="h-4 w-4" />Email</a>@endif
                    </div>
                </div>
            </section>

            <section class="a-card p-5" aria-labelledby="req-title">
                <h2 id="req-title" class="font-semibold">Request</h2>
                @if ($rows)
                    <dl class="facts mt-3 max-w-2xl">@foreach ($rows as $k => $v)<div><dt>{{ $k }}</dt><dd>{{ $v }}</dd></div>@endforeach</dl>
                @endif
                @if ($lead->type === LeadType::Order)
                    @if ($lines)
                        <div class="table-box mt-5">
                            <table class="data-table min-w-[28rem] rounded border border-line">
                                <thead><tr><th>Line</th><th>Case</th><th class="r">Cases</th></tr></thead>
                                <tbody>
                                    @foreach ($lines as $line)
                                        <tr><td><span class="text-ink-3">{{ $line['brand'] }}</span> · <span class="font-semibold">{{ $line['name'] }}</span></td><td class="num whitespace-nowrap">{{ $line['pack'] ?? '—' }}</td><td class="r font-semibold">{{ $line['cases'] }}</td></tr>
                                    @endforeach
                                </tbody>
                                <tfoot><tr><td colspan="2" class="font-semibold">Total</td><td class="r font-bold">{{ $lead->caseCount() }}</td></tr></tfoot>
                            </table>
                        </div>
                    @endif
                    @if (! empty($d['other_items']))
                        <h3 class="mt-5 text-sm font-semibold text-ink-3">Other items</h3>
                        <p class="mt-1 whitespace-pre-line rounded bg-surface-2 p-4">{{ $d['other_items'] }}</p>
                    @endif
                @endif
                @if ($lead->message && (! $isAccount || $canSeeCredit))
                    <blockquote class="mt-4 whitespace-pre-line rounded bg-surface-2 p-4">{{ $lead->message }}</blockquote>
                @endif
                @if ($lead->source_url)<p class="mt-3 truncate text-xs text-ink-3">From {{ $lead->source_url }}</p>@endif
            </section>

            @if ($isAccount)
                <section class="a-card p-5" aria-labelledby="credit-title">
                    <h2 id="credit-title" class="flex items-center gap-2 font-semibold"><x-icon name="lock" class="h-4 w-4 text-ink-3" />Credit application</h2>
                    @if ($canSeeCredit)
                        <dl class="facts mt-3 max-w-2xl">
                            @foreach (array_filter([
                                'Trading as' => $d['trading_as'] ?? null,
                                'Company registration' => $d['registration_no'] ?? null,
                                'Trade licence no.' => $d['trade_licence_no'] ?? null,
                                'VAT' => ! empty($d['vat_registered']) ? 'Registered · '.($d['vat_no'] ?? '') : 'Not VAT-registered',
                                'Time trading' => peterco('years_trading.'.($d['years_trading'] ?? '')),
                                'Outlets' => $d['outlets'] ?? null,
                                'Address' => $d['address'] ?? null,
                                'Pharmacy licence no.' => $d['pharmacy_licence_no'] ?? null,
                                'Pharmacist / medical director' => $d['pharmacist'] ?? null,
                                'Terms asked for' => \App\Enums\PaymentTerms::tryFrom((string) ($d['terms'] ?? ''))?->label(),
                                'Credit limit asked for' => isset($d['credit_limit']) ? peterco('currency').' '.number_format((int) $d['credit_limit']) : null,
                                'Bank' => $d['bank_name'] ?? null,
                                'Trade references' => collect($d['references'] ?? [])->map(fn ($r) => $r['name'].' ('.$r['phone'].')')->join('; ') ?: null,
                                'Consent to checks' => $lead->consented_at?->format('j M Y H:i'),
                            ], fn ($v) => $v !== null && $v !== '') as $k => $v)
                                <div><dt>{{ $k }}</dt><dd>{{ $v }}</dd></div>
                            @endforeach
                        </dl>
                        @if ($lead->tradeAccount)
                            <p class="mt-4 flex items-center gap-2 font-semibold"><x-icon name="check-circle" class="h-5 w-5 text-[color:var(--good)]" />Account {{ $lead->tradeAccount->number }} open on {{ $lead->tradeAccount->terms->short() }} terms.</p>
                        @endif
                    @else
                        <p class="mt-2 text-ink-2">Licence numbers, VAT, references, the credit request and the documents are visible to credit control only.</p>
                    @endif
                </section>
            @endif

            @if ($lead->files->isNotEmpty())
                <section class="a-card p-5" aria-labelledby="files-title">
                    <h2 id="files-title" class="font-semibold">Uploads</h2>
                    <ul class="mt-3 divide-y divide-line">
                        @foreach ($lead->files as $f)
                            <li class="flex flex-wrap items-center gap-3 py-3">
                                <x-icon :name="$f->isImage() ? 'image' : 'file'" class="h-5 w-5 text-ink-3" />
                                <div class="min-w-0 flex-1"><p class="font-semibold">{{ $f->label() }}</p><p class="truncate text-sm text-ink-3">{{ $f->original_name }} · {{ number_format($f->size / 1024) }} KB</p></div>
                                @can('openFile', [$lead, $f])
                                    <a href="{{ route('admin.leads.file', [$lead, $f]) }}" class="a-btn a-btn-ghost" target="_blank" rel="noopener">{{ $f->isImage() ? 'View' : 'Open' }}</a>
                                @else
                                    <span class="chip" data-tone="muted"><x-icon name="lock" />{{ $f->restrictedTo() ?? 'Restricted' }}</span>
                                @endcan
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <div class="min-w-0 space-y-6">
            @if ($canDecide && ! $lead->tradeAccount && $lead->status !== \App\Enums\LeadStatus::Declined)
                <section class="a-card border-l-4 border-l-[color:var(--primary)] p-5" aria-labelledby="approve-title">
                    <h2 id="approve-title" class="font-semibold">Approve and open account</h2>
                    <p class="mt-1 text-sm text-ink-2">Creates the trade account, gives it a PC- number and emails the customer.</p>
                    <form method="POST" action="{{ route('admin.credit.approve', $lead) }}" class="mt-4 space-y-3">
                        @csrf
                        <div><label for="terms" class="mb-1 block text-sm font-semibold">Terms</label>
                            <select id="terms" name="terms" class="a-input w-full">@foreach (\App\Enums\PaymentTerms::cases() as $t)<option value="{{ $t->value }}" @selected(($d['terms'] ?? 'cod') === $t->value)>{{ $t->label() }}</option>@endforeach</select></div>
                        <div><label for="anote" class="mb-1 block text-sm font-semibold">Note <span class="font-normal text-ink-3">(optional)</span></label>
                            <input id="anote" name="note" class="a-input w-full" maxlength="1000" placeholder="Limit agreed, references checked…"></div>
                        <button class="a-btn a-btn-primary w-full"><x-icon name="check" class="h-4 w-4" />Approve and open account</button>
                    </form>
                </section>
            @endif

            <section class="a-card p-5" aria-labelledby="manage-title">
                <h2 id="manage-title" class="font-semibold">Manage</h2>
                <form method="POST" action="{{ route('admin.leads.update', $lead) }}" class="mt-3 space-y-4">
                    @csrf @method('PATCH')
                    @if (! $isAccount || $canDecide)
                        <div><label for="status" class="mb-1 block text-sm font-semibold">Stage</label>
                            <select id="status" name="status" class="a-input w-full" @disabled($isAccount && $lead->status === \App\Enums\LeadStatus::Approved)>
                                @foreach ($lead->type->statuses() as $s)
                                    @continue($isAccount && $s === \App\Enums\LeadStatus::Approved && $lead->status !== $s)
                                    <option value="{{ $s->value }}" @selected($lead->status === $s)>{{ $s->label() }}</option>
                                @endforeach
                            </select></div>
                    @else
                        <p class="text-sm text-ink-2">Credit control sets the stage of trade account applications.</p>
                    @endif
                    <div><label for="assigned_to" class="mb-1 block text-sm font-semibold">Assigned to</label>
                        <select id="assigned_to" name="assigned_to" class="a-input w-full"><option value="">Nobody</option>@foreach ($staff as $u)<option value="{{ $u->id }}" @selected($lead->assigned_to === $u->id)>{{ $u->name }} ({{ $u->role->label() }})</option>@endforeach</select></div>
                    @if ($lead->type === LeadType::Order)
                        <div><label for="delivery_date" class="mb-1 block text-sm font-semibold">Delivery day</label><input id="delivery_date" type="date" name="delivery_date" value="{{ $lead->delivery_date?->toDateString() }}" class="a-input w-full"></div>
                    @endif
                    <button class="a-btn a-btn-primary w-full">Save</button>
                </form>
            </section>

            <section id="timeline" class="a-card p-5" aria-labelledby="tl-title">
                <h2 id="tl-title" class="font-semibold">Timeline</h2>
                <form method="POST" action="{{ route('admin.leads.note', $lead) }}" class="mt-3 space-y-2">
                    @csrf
                    <label for="body" class="sr-only">Add a note</label>
                    <textarea id="body" name="body" rows="3" class="a-input w-full" placeholder="Called, confirmed prices, delivery Thursday…" required maxlength="3000"></textarea>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" name="contacted" value="1" class="h-4 w-4" @checked($lead->status === \App\Enums\LeadStatus::New)>I spoke to the customer</label>
                        <button class="a-btn a-btn-primary">Add note</button>
                    </div>
                </form>
                <ol class="mt-5 space-y-4 border-l-2 border-line pl-5">
                    @foreach ($lead->notes as $n)
                        <li class="relative">
                            <span @class(['absolute -left-[1.6rem] top-1.5 h-2.5 w-2.5 rounded-sm', 'bg-[color:var(--stripe)]' => $n->kind === 'note', 'bg-[color:var(--series-1)]' => in_array($n->kind, ['status', 'assign', 'credit'], true), 'bg-line-strong' => in_array($n->kind, ['system', 'file'], true)]) aria-hidden="true"></span>
                            <p @class(['whitespace-pre-line', 'text-ink-2' => $n->kind !== 'note'])>{{ $n->body }}</p>
                            <p class="mt-0.5 text-xs text-ink-3">{{ $n->user->name ?? 'Website' }} · {{ $n->created_at->format('j M, H:i') }}</p>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>
    </div>
</x-layouts.admin>
