<x-layouts.site title="Careers" description="Jobs at Acado St. Lucia: warehouse, delivery, merchandising, sales and office roles in Cul De Sac and around the island.">
    <x-page-hero title="Careers at Acado St. Lucia" lead="Warehouse, delivery, merchandising, sales and office roles, based at Cul De Sac and out on the road across the island. Apply for an open vacancy or send a general application." label="Careers" photo="acado-careers" alt="A smiling woman working on a laptop" position="70% 30%">
        @php($open = $vacancies->count())
        <p class="mt-5 inline-flex flex-wrap items-center gap-2 font-display text-lg font-semibold text-ink">
            <span class="careers-pill"><span class="num" @if ($open) data-count="{{ $open }}" style="min-width: {{ strlen((string) $open) }}ch" @endif>{{ $open }}</span> open {{ \Illuminate\Support\Str::plural('vacancy', $open) }}</span>
            <span class="text-base font-medium text-ink-2">· general applications welcome any time</span>
        </p>
        <div class="mt-6 flex flex-wrap gap-3">
            @if ($open)
                <a href="#vacancies" class="btn btn-primary">See open vacancies<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                <a href="{{ route('careers.apply') }}" class="btn btn-outline">Send a general application</a>
            @else
                <a href="{{ route('careers.apply') }}" class="btn btn-primary">Send a general application<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
            @endif
        </div>
    </x-page-hero>

    <section class="wrap mt-12 md:mt-16">
        <div class="grid gap-12 lg:grid-cols-[1.25fr_0.75fr] lg:items-start">
            <div class="min-w-0">
                <h2 id="vacancies" class="display-3">Open vacancies</h2>
                @if ($vacancies->isEmpty())
                    <div class="carton mt-6 p-6"><p class="font-bold">No open vacancies right now.</p><p class="mt-1 text-ink-2">Send a general application and HR will keep your CV on file for the next opening.</p></div>
                @else
                    <ul class="mt-6 grid gap-4">
                        @foreach ($vacancies as $v)
                            <li class="card-link carton lift vacancy-card p-5 sm:flex sm:items-center sm:gap-6" data-reveal style="--reveal-delay: {{ $loop->index * 90 }}ms">
                                <div class="min-w-0 flex-1">
                                    <p class="kicker text-ink-3">{{ $v->department }}</p>
                                    <h3 class="mt-1 text-xl font-bold"><a href="{{ route('careers.show', $v) }}" class="stretch">{{ $v->title }}</a></h3>
                                    <p class="mt-1 text-ink-2">{{ $v->summary }}</p>
                                    <p class="mt-3 flex flex-wrap gap-2">
                                        <x-chip icon="map-pin">{{ $v->location }}</x-chip><x-chip icon="clock">{{ $v->employment_type }}</x-chip>
                                        @if ($v->closes_on)<x-chip icon="calendar">Closes {{ $v->closes_on->format('j M Y') }}</x-chip>@endif
                                        @if ($v->is_demo)<span class="sample-badge">Sample vacancy</span>@endif
                                    </p>
                                </div>
                                <span class="vacancy-go mt-4 hidden h-11 w-11 flex-none items-center justify-center rounded-full bg-primary-soft text-link sm:inline-flex" aria-hidden="true"><x-icon name="arrow-right" class="h-5 w-5" /></span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <aside class="space-y-5 lg:sticky lg:top-28">
                <div class="carton p-6">
                    <h2 class="text-lg font-bold">General application</h2>
                    <p class="mt-2 text-[0.9375rem] text-ink-2">Not seeing the right role? Send your CV and tell us the kind of work you want. HR keeps it on file for twelve months.</p>
                    <a href="{{ route('careers.apply') }}" class="btn btn-primary mt-5 w-full">Send your CV</a>
                </div>
                <div class="carton p-6">
                    <h2 class="text-lg font-bold">What we value</h2>
                    <p class="mt-2 text-[0.9375rem] text-ink-2">People centered, integrity, trust, ownership and entrepreneurial spirit: the values Acado St. Lucia publishes on its own website.</p>
                </div>
            </aside>
        </div>
    </section>
</x-layouts.site>
