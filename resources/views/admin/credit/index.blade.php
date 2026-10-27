<x-layouts.admin title="Credit board">
    <p class="max-w-3xl text-ink-2">Trade account applications by stage, oldest first. Decided applications stay on the board for 60 days. Documents and credit details open on each application, for credit control only.</p>
    <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-5">
        @foreach ($columns as $col)
            <section class="a-card flex min-w-0 flex-col" aria-labelledby="col-{{ $col['status']->value }}">
                <header class="flex items-center justify-between gap-2 border-b border-line px-4 py-3">
                    <h2 id="col-{{ $col['status']->value }}"><x-status :status="$col['status']" /></h2>
                    <span class="num text-sm font-semibold text-ink-3">{{ $col['leads']->count() }}</span>
                </header>
                <ul class="flex-1 divide-y divide-line">
                    @forelse ($col['leads'] as $l)
                        <li>
                            <a href="{{ route('admin.leads.show', $l) }}" class="block px-4 py-3 hover:bg-surface-2">
                                <p class="font-semibold leading-snug">{{ $l->business_name ?? $l->name }}</p>
                                <p class="num text-sm text-ink-3">{{ $l->reference }} · {{ \App\Enums\PaymentTerms::tryFrom((string) $l->detail('terms'))?->short() ?? '' }}</p>
                                <p class="mt-1 flex flex-wrap items-center gap-1.5 text-xs text-ink-3">
                                    {{ $l->detail('trade_type_label') }}
                                    @if (\App\Enums\TradeType::tryFrom((string) $l->detail('trade_type'))?->isMedical())<span class="chip" data-tone="neutral"><x-icon name="pill" />Licence</span>@endif
                                    @if ($l->isOverdue())<span class="chip" data-tone="critical"><x-icon name="clock" />{{ $l->created_at->diffForHumans(null, true, true) }}</span>@else<span>{{ $l->created_at->format('j M') }}</span>@endif
                                </p>
                            </a>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-sm text-ink-3">None.</li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>
</x-layouts.admin>
