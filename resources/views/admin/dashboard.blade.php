<x-layouts.admin title="Overview">
    @if ($notifyMissing)
        <div class="mb-5 flex gap-3 rounded-md border border-line bg-surface p-4 text-sm" role="status"><x-icon name="mail" class="h-5 w-5 flex-none text-link" /><p><strong>Nobody is emailed about new requests yet.</strong> Set <code>PETERCO_NOTIFY_EMAIL</code> (and optionally <code>PETERCO_NOTIFY_ORDERS</code>, <code>_CREDIT</code>, <code>_HR</code>, <code>_PRINCIPALS</code>) in <code>.env</code>.</p></div>
    @elseif ($notifyInvalid)
        <div class="mb-5 flex gap-3 rounded-md border border-line bg-surface p-4 text-sm" role="status"><x-icon name="alert" class="h-5 w-5 flex-none text-[color:var(--critical)]" /><p>These notification addresses look wrong and are skipped: {{ implode(', ', $notifyInvalid) }}</p></div>
    @endif

    {{-- The one number to act on: requests past their response target --}}
    <section class="a-card overflow-hidden" aria-labelledby="overdue-title">
        <div class="grid gap-6 p-6 md:grid-cols-[auto_1fr_auto] md:items-center md:p-8">
            <x-stencil :text="str_pad((string) $overdueCount, 2, '0', STR_PAD_LEFT)" @class(['text-6xl', 'text-[color:var(--critical-text)]' => $overdueCount > 0]) />
            <div>
                <h2 id="overdue-title" class="text-xl font-bold">{{ $overdueCount === 1 ? 'request is' : 'requests are' }} past the response target</h2>
                <p class="mt-1 text-ink-2">
                    @if ($overdueCount)
                        The oldest arrived {{ $oldestOverdue?->diffForHumans() }}. Targets: {{ collect($types)->map(fn ($t) => $t->label().' '.$t->targetLabel())->join(', ') }}.
                    @else
                        Every request has been picked up in time. {{ $newCount }} still new but inside the target.
                    @endif
                </p>
                @if ($overdueByType->isNotEmpty())
                    <p class="mt-3 flex flex-wrap gap-2">
                        @foreach ($overdueByType as $row)
                            <a href="{{ route('admin.leads.index', ['overdue' => 1, 'type' => $row['type']->value]) }}" class="chip num min-h-8 hover:bg-line" data-tone="critical"><x-icon :name="$row['type']->icon()" />{{ $row['count'] }} {{ \Illuminate\Support\Str::lower($row['type']->label()) }}</a>
                        @endforeach
                    </p>
                @endif
            </div>
            <a href="{{ route('admin.leads.index', $overdueCount ? ['overdue' => 1] : ['status' => 'new']) }}" class="a-btn {{ $overdueCount ? 'a-btn-primary' : 'a-btn-ghost' }}">{{ $overdueCount ? 'Pick them up now' : 'Open new requests' }}<x-icon name="arrow-right" class="h-4 w-4" /></a>
        </div>
        <div class="h-1.5 bg-[color:var(--stripe)]" aria-hidden="true"></div>
    </section>

    <div @class(['mt-6 grid gap-4 sm:grid-cols-2', 'xl:grid-cols-4' => count($tiles) === 4, 'xl:grid-cols-3' => count($tiles) === 3])>
        @foreach ($tiles as $t)
            @if (($t['format'] ?? null) === 'minutes')
                <a href="{{ $t['href'] }}" class="a-card block p-5 transition hover:shadow-lg">
                    <p class="text-sm text-ink-2">{{ $t['label'] }}</p>
                    <p class="mt-2 text-[2rem] font-semibold leading-none tracking-tight num">{{ $t['value'] === null ? '—' : ($t['value'] >= 120 ? round($t['value'] / 60, 1).' h' : $t['value'].' min') }}</p>
                    <p class="mt-3 text-sm text-ink-3">{{ $t['note'] }}</p>
                </a>
            @else
                <x-stat-tile :label="$t['label']" :value="$t['value']" :previous="$t['previous'] ?? null" :period="$t['period'] ?? null" :note="$t['note'] ?? null" :href="$t['href']" />
            @endif
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.5fr_1fr]">
        <section class="a-card min-w-0 p-5" aria-labelledby="chart-title">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 id="chart-title" class="font-semibold">Requests per day, last {{ $chartDays }} days</h2>
                <p class="text-sm text-ink-3 num">{{ $daily->sum('count') }} total</p>
            </div>
            <div class="relative mt-5" x-data="columnChart" @mouseleave="hide()">
                <svg viewBox="0 0 {{ $chartDays * 10 }} 120" class="h-44 w-full" preserveAspectRatio="none" role="img" aria-label="Column chart of requests per day">
                    @foreach ($daily as $i => $d)
                        @php($h = $d['count'] / $dailyMax * 110)
                        <rect class="bar" x="{{ $i * 10 + 1.5 }}" y="{{ 120 - max($h, 1) }}" width="7" height="{{ max($h, 1) }}" rx="1.5" fill="{{ $d['count'] ? 'var(--series-1)' : 'var(--series-track)' }}"
                            data-label="{{ $d['date']->format('D j M') }}" data-value="{{ $d['count'] }}" tabindex="0" @mouseenter="show($el)" @focus="show($el)" @blur="hide()"><title>{{ $d['date']->format('D j M') }}: {{ $d['count'] }}</title></rect>
                    @endforeach
                </svg>
                <div x-show="tip" x-cloak class="pointer-events-none absolute -translate-x-1/2 -translate-y-full rounded-md bg-crate-900 px-2.5 py-1.5 text-xs font-semibold text-white shadow" :style="tip && `left:${tip.x}px; top:${tip.y - 6}px`"><span x-text="tip?.label"></span>: <span x-text="tip?.value"></span></div>
                <div class="mt-2 flex justify-between text-xs text-ink-3"><span>{{ $daily->first()['date']->format('j M') }}</span><span>Today</span></div>
            </div>
        </section>
        <section class="a-card min-w-0 p-5" aria-labelledby="type-title">
            <h2 id="type-title" class="font-semibold">By type, last {{ $chartDays }} days</h2>
            @php($maxType = max(1, $byType->max('count')))
            <ul class="mt-3 space-y-1">
                @foreach ($byType as $row)
                    <li>
                        <a href="{{ route('admin.leads.index', ['type' => $row['type']->value]) }}" class="block min-h-11 rounded px-1 py-1.5 hover:bg-surface-2">
                            <div class="flex justify-between text-sm"><span class="inline-flex items-center gap-2"><x-icon :name="$row['type']->icon()" class="h-4 w-4 text-ink-3" />{{ $row['type']->label() }}</span><span class="num font-semibold">{{ $row['count'] }}</span></div>
                            <div class="meter mt-1.5"><span style="width: {{ $row['count'] / $maxType * 100 }}%"></span></div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="a-card min-w-0" aria-labelledby="queue-title">
            <div class="flex items-center justify-between gap-3 px-5 pb-1.5 pt-3.5"><h2 id="queue-title" class="font-semibold">Call next (oldest new first)</h2><a class="inline-flex min-h-11 flex-none items-center text-sm text-link underline" href="{{ route('admin.leads.index', ['status' => 'new']) }}">All new</a></div>
            @forelse ($queue as $l)
                <a href="{{ route('admin.leads.show', $l) }}" class="flex items-center gap-3 border-t border-line px-5 py-3 hover:bg-surface-2">
                    <x-icon :name="$l->type->icon()" class="h-5 w-5 flex-none text-ink-3" />
                    <div class="min-w-0 flex-1"><p class="truncate font-semibold">{{ $l->business_name ?? $l->name }}</p><p class="truncate text-sm text-ink-3">{{ $l->type->label() }}@if($l->subject()) · {{ $l->subject() }}@endif</p></div>
                    <span @class(['chip num', 'font-bold']) data-tone="{{ $l->isOverdue() ? 'critical' : 'neutral' }}"><x-icon name="clock" />{{ $l->created_at->diffForHumans(null, true, true) }}</span>
                </a>
            @empty
                <p class="border-t border-line px-5 py-6 text-ink-3">Nothing new waiting. Nice work.</p>
            @endforelse
        </section>
        @if (auth()->user()->canWork(\App\Enums\LeadType::Order))
        <section class="a-card min-w-0" aria-labelledby="up-title">
            <div class="p-5 pb-3"><h2 id="up-title" class="font-semibold">Requested deliveries, next working days</h2></div>
            @forelse ($deliveries as $l)
                <a href="{{ route('admin.leads.show', $l) }}" class="flex items-center gap-3 border-t border-line px-5 py-3 hover:bg-surface-2">
                    <div class="w-20 flex-none text-sm"><p class="font-semibold">{{ $l->delivery_date->isToday() ? 'Today' : $l->delivery_date->format('D j') }}</p><p class="text-ink-3">{{ peterco('districts.'.$l->detail('district')) ?? '' }}</p></div>
                    <div class="min-w-0 flex-1"><p class="truncate font-semibold">{{ $l->business_name ?? $l->name }}</p><p class="truncate text-sm text-ink-3">{{ $l->reference }} · {{ $l->caseCount() }} cases</p></div>
                    <x-status :status="$l->status" />
                </a>
            @empty
                <p class="border-t border-line px-5 py-6 text-ink-3">No delivery days requested in the next few days.</p>
            @endforelse
        </section>
        @endif
    </div>
</x-layouts.admin>
