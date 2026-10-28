@php
    $divisions = (array) peterco('divisions');
    $brandCount = collect($divisions)->sum(fn (array $d) => count($d['brands']));
    // Two marquee rows: Acado's 24 featured brands, then 24 more from the division pages that have logo tiles.
    $featuredBrands = (array) peterco('featured_brands');
    $moreBrands = collect($divisions)->flatMap(fn (array $d) => $d['brands'])->reject(fn (string $b) => in_array($b, $featuredBrands, true) || brand_logo($b) === null)->unique()->values();
    $marqueeRows = [array_values($featuredBrands), $moreBrands->filter(fn ($b, $i) => $i % 3 === 0)->take(24)->values()->all()];
    $divisionOf = fn (string $brand) => collect($divisions)->search(fn (array $d) => in_array($brand, $d['brands'], true)) ?: 'wines-spirits';
@endphp
<x-layouts.site>
    {{-- Hero: the company's own photo (staff at the Acado St. Lucia launch) under a navy scrim, with the trade actions --}}
    <section class="hero relative isolate overflow-hidden bg-navy-950 text-white on-dark" data-hero="home" data-inview>
        {{-- Phones: the photo on top, fading into navy under the text. Desktop: full-bleed under a scrim. --}}
        <div class="hero-photo relative aspect-[4/3] sm:aspect-[16/9] lg:absolute lg:inset-0 lg:-z-10 lg:aspect-auto">
            <x-photo key="acado-team" alt="Acado St. Lucia staff gathered at the unveiling of the new Acado sign in February 2025" :eager="true" sizes="100vw" class="h-full w-full !bg-navy-950" position="60% 40%" />
            <div class="absolute inset-x-0 bottom-0 h-2/5 bg-gradient-to-t from-navy-950 to-transparent lg:hidden" aria-hidden="true"></div>
        </div>
        <div class="hero-scrim absolute inset-0 -z-10 hidden lg:block" aria-hidden="true"></div>
        {{-- Motion graphic: the Acado mark's four leaves assemble, then turn slowly behind the headline --}}
        <x-acado-mark variant="line" class="hero-orbit" />
        <div class="wrap relative -mt-10 pb-24 sm:-mt-16 lg:mt-0 lg:flex lg:min-h-[44rem] lg:items-center lg:pb-32 lg:pt-20">
            <div class="max-w-2xl">
                <p class="rack-tag hero-eyebrow"><x-acado-mark class="hero-mark" />Formerly Peter &amp; Company Distribution</p>
                {{-- Word-by-word entrance; the words are plain text in the heading, so it reads and indexes normally --}}
                <h1 class="display-1 hero-title mt-5 text-white">@foreach (explode(' ', "St Lucia's shelves, stocked from Cul De Sac.") as $w)<span class="hw"><span style="--w: {{ $loop->index }}">{{ $w }}</span></span>@if (! $loop->last) @endif @endforeach</h1>
                <p class="lead hero-in mt-5 text-white/90" style="--d: 1">Acado St. Lucia distributes wines and spirits, beer and beverages, food, household and personal care, and pharmaceuticals to the island's supermarkets, minimarts, bars, hotels and pharmacies.</p>
                <div class="hero-in mt-8 flex flex-wrap gap-3" style="--d: 2">
                    <a href="{{ route('catalogue') }}" class="btn btn-primary">Browse the trade catalogue<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                    <a href="{{ route('account.create') }}" class="btn btn-on-dark">Open a trade account</a>
                </div>
            </div>
        </div>
        <div class="gradient-rule absolute inset-x-0 bottom-0" aria-hidden="true"></div>
    </section>

    {{-- Sourced facts, on a card that overlaps the hero --}}
    <section class="wrap relative z-10 -mt-12" aria-label="The company in figures">
        <dl class="grid gap-px overflow-hidden rounded-[var(--radius)] border border-line bg-line shadow-lg sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['Trading since', '1891', 'as Peter &amp; Company'],
                ['Divisions', (string) count($divisions), collect($divisions)->pluck('short')->map(fn ($s) => e($s))->join(', ', ' and ')],
                ['Brands', (string) $brandCount, 'on our division pages, including Carib, El Dorado and Hennessy'],
                ['Part of', 'Acado', 'Limited, the Agostini\'s and Goddard Enterprises joint venture'],
            ] as [$dt, $big, $small])
                <div class="bg-surface p-5 lg:p-6">
                    <dt class="kicker text-ink-3">{{ $dt }}</dt>
                    <dd class="mt-1"><span class="font-display num text-3xl font-semibold text-title" @if (ctype_digit($big)) data-count="{{ $big }}" style="min-width: {{ strlen($big) }}ch" @endif>{{ $big }}</span> <span class="text-[0.9375rem] text-ink-2">{!! $small !!}</span></dd>
                </div>
            @endforeach
        </dl>
    </section>

    {{-- "I'd like to…": every trade action, one tap away --}}
    <section class="wrap mt-16 md:mt-20" aria-labelledby="todo-title">
        <h2 id="todo-title" class="kicker text-ink-3">I'd like to…</h2>
        <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ([
                ['catalogue', 'Browse the catalogue', 'Every line we stock, by brand and division. No login needed.', 'list'],
                ['order.show', 'Request an order', 'Build a list and send it. The trade desk confirms price and delivery.', 'crate'],
                ['account.create', 'Open a trade account', 'Cash or credit terms, with your licence and VAT documents.', 'shield'],
                ['principals', 'Distribute my brand', 'Overseas brand owners looking for St Lucia distribution.', 'globe'],
                ['careers.index', 'Find a job', 'Warehouse, delivery, merchandising and sales roles.', 'briefcase'],
            ] as [$route, $label, $text, $icon])
                <li class="card-link carton lift p-5">
                    <span class="grid h-11 w-11 place-items-center rounded-full bg-primary-soft text-primary"><x-icon :name="$icon" class="h-5 w-5" /></span>
                    <h3 class="mt-4 text-lg font-semibold leading-snug"><a href="{{ route($route) }}" class="stretch">{{ $label }}</a></h3>
                    <p class="mt-1 text-[0.9375rem] text-ink-2">{{ $text }}</p>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Acado's grey "About us" band --}}
    <section class="band-grey mt-20 md:mt-24" aria-labelledby="about-title">
        <div class="wrap grid gap-8 py-16 md:py-20 lg:grid-cols-[0.75fr_1.25fr] lg:items-center lg:gap-16">
            <h2 id="about-title" class="section-title">About us</h2>
            <div>
                <p class="font-display text-xl font-medium leading-snug text-primary md:text-2xl">Acado St. Lucia Limited, formerly Peter &amp; Company Limited, is a premier distributor of Fast-Moving Consumer Goods (FMCG) in St. Lucia.</p>
                <p class="mt-5 max-w-[65ch]">With roots dating back to the 1890s, Acado St. Lucia is one of the island's largest and most progressive distributors of consumer products. The company was founded in 1891 as Peter &amp; Company by William Peter, initially operating as a coaling business. In 1961, J. N. Goddard &amp; Sons of Barbados acquired a majority stake.</p>
                <p class="mt-4 max-w-[65ch]">Over time, the company expanded into food and beverage, building materials and Nissan dealerships, and developed its own retail stores: PCD Food Mart, PCD Wines &amp; Spirits and Wet &amp; Cold Express.</p>
                <a href="{{ route('about') }}" class="btn btn-outline mt-8">Discover who we are<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
            </div>
        </div>
    </section>

    {{-- Acado's orange "Our portfolio" band: the four divisions --}}
    <section class="band-orange on-orange" aria-labelledby="div-title">
        <div class="wrap py-16 md:py-20">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h2 id="div-title" class="section-title !text-white">Our portfolio</h2>
                    <p class="font-display mt-3 max-w-xl text-2xl font-semibold text-white">Four divisions, one delivery.</p>
                </div>
                <a href="{{ route('brands.index') }}" class="btn btn-white">All {{ $brandCount }} brands<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
            </div>
            <ul class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($divisions as $key => $d)
                    <li class="tilt-wrap" data-reveal style="--reveal-delay: {{ $loop->index * 80 }}ms"><div class="tilt card-link group flex h-full flex-col overflow-hidden rounded-[var(--radius)] bg-surface shadow-lg" data-tilt>
                        <div class="card-img tilt-img grid aspect-[4/3] place-items-center bg-white">
                            <img src="{{ asset('img/portfolio/'.$key.'.webp') }}" alt="" width="250" height="250" loading="lazy" decoding="async" class="h-[88%] w-auto">
                        </div>
                        <div class="flex flex-1 flex-col p-5">
                            <h3 class="text-lg font-semibold leading-snug"><a href="{{ route('brands.show', $key) }}" class="stretch">{{ $d['name'] }}</a></h3>
                            <p class="mt-2 text-[0.9375rem] text-ink-2">{{ $d['blurb'] }}</p>
                            <p class="mt-auto flex flex-wrap items-center gap-2 pt-4 text-sm font-semibold text-primary">
                                {{ count($d['brands']) }} brands<x-icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-1" />
                                @if (! empty($d['restricted']))<span class="chip ml-auto" data-tone="attention"><x-icon name="lock" />Licensed buyers only</span>@endif
                            </p>
                        </div>
                        <span class="tilt-glare" aria-hidden="true"></span>
                    </div></li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- From the catalogue --}}
    @if ($featured->isNotEmpty())
        <section class="wrap mt-20 md:mt-24" aria-labelledby="cat-title">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <x-rack-tag label="Trade catalogue" />
                    <h2 id="cat-title" class="display-2 mt-3 max-w-2xl">Add it to your order list.</h2>
                    <p class="mt-3 max-w-[60ch] text-ink-2">Pick the cases you need and send the list. Prices, availability and your delivery day are confirmed by the trade desk, so you never pay for something that isn't on the truck.</p>
                </div>
                <a href="{{ route('catalogue') }}" class="btn btn-outline">All {{ $productCount }} lines<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
            </div>
            <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($featured as $p)
                    <x-product-card :product="$p" :on-list="\App\Support\OrderList::items()[$p->id] ?? 0" :class="$loop->index >= 4 ? 'max-sm:hidden' : ''" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Acado's "Featured brands" logo grid --}}
    <section class="wrap mt-20 md:mt-28" aria-labelledby="brands-title">
        <div class="text-center">
            <h2 id="brands-title" class="section-title">Featured brands</h2>
            <p class="mx-auto mt-3 max-w-[60ch] text-ink-2">A few of the {{ $brandCount }} brands we distribute. Choose one to see its division.</p>
        </div>
        <x-logo-marquee class="mt-10" :rows="$marqueeRows" :division-of="$divisionOf" />
        <div class="mt-8 text-center"><a href="{{ route('brands.index') }}" class="btn btn-outline">See every brand, by division<x-icon name="arrow-right" class="nudge h-4 w-4" /></a></div>
    </section>

    {{-- How ordering works: on desktop the panel stays put while the steps scroll past, and the van moves to each stop --}}
    <section class="band-grey mt-20 md:mt-28" aria-labelledby="how-title">
        <div class="wrap grid gap-12 py-16 md:py-20 lg:grid-cols-[0.95fr_1.05fr] lg:items-start" data-steps data-step="4">
            <div class="lg:sticky lg:top-32">
                <x-rack-tag label="How trade ordering works" />
                <h2 id="how-title" class="display-2 mt-3">From list to loading bay in four steps.</h2>
                <p class="mt-4 max-w-[60ch] text-ink-2">The website takes the request; your sales representative and the trade desk do what they always have. Nothing is charged online.</p>
                <div class="route-panel mt-8" aria-hidden="true">
                    <div class="route-track">
                        <span class="route-fill"></span>
                        @foreach (['list', 'inbox', 'phone', 'check-circle'] as $i => $icon)
                            <span class="route-stop" style="--s: {{ $i }}"><x-icon :name="$icon" class="h-5 w-5" /></span>
                        @endforeach
                        <span class="route-van"><x-icon name="truck" class="h-8 w-8" /></span>
                    </div>
                    <div class="route-labels"><span>List</span><span>Sent</span><span>Confirmed</span><span>Delivered</span></div>
                </div>
                <a href="{{ route('order.show') }}" class="btn btn-primary mt-8">Start an order list<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
            </div>
            <ol class="steps-list grid gap-4">
                @foreach ([
                    ['Build your list', 'Add cases from the catalogue, or type in anything you can\'t find. Works on any phone.'],
                    ['Send it with your account number', 'Approved trade accounts are matched straight away. No account yet? Send it anyway.'],
                    ['The trade desk calls you', 'Prices, availability, substitutions and the delivery day, confirmed by phone or WhatsApp.'],
                    ['Delivered from Cul De Sac', 'Your reference number follows the order from the warehouse to your door.'],
                ] as $i => [$h, $t])
                    <li class="step-card carton p-6" data-step-n="{{ $i + 1 }}">
                        <x-stencil :text="sprintf('%02d', $i + 1)" class="text-3xl text-title" />
                        <h3 class="mt-2 text-lg font-semibold">{{ $h }}</h3>
                        <p class="mt-1 text-[0.9375rem] text-ink-2">{{ $t }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Island-wide delivery map --}}
    <section class="wrap mt-20 md:mt-28" aria-labelledby="map-title">
        <x-delivery-map>
            <x-rack-tag label="Island-wide delivery" />
            <h2 id="map-title" class="display-2 mt-3">Every district, out of Cul De Sac.</h2>
            <p class="mt-4 max-w-[60ch] text-ink-2">The trucks leave the Cul De Sac warehouse for all ten districts. Point at a district on the map, or tab to it, to see its delivery days; they are all listed here too.</p>
        </x-delivery-map>
    </section>

    {{-- Pharmacies --}}
    <section class="wrap mt-20 md:mt-28" aria-labelledby="rx-title">
        <div class="grid overflow-hidden rounded-[var(--radius)] border border-line bg-surface shadow-md md:grid-cols-[0.8fr_1.2fr]">
            <div class="photo aspect-square bg-white md:aspect-auto"><x-photo key="division-pharmaceutical" alt="A shopping cart filled with medicine packs, from Acado St. Lucia's pharmaceutical division" sizes="(min-width: 768px) 40vw, 100vw" class="h-full w-full !bg-white" /></div>
            <div class="flex flex-col justify-center p-7 md:p-10">
                <x-rack-tag label="Pharmaceuticals" />
                <h2 id="rx-title" class="display-3 mt-3">For licensed pharmacies, hospitals and clinics.</h2>
                <p class="mt-3 text-ink-2">{{ $divisions['pharmaceutical']['blurb'] }} Pharmaceutical orders are accepted only from approved pharmacy and clinic accounts with a licence on file. We do not sell medicines to the public.</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('brands.show', 'pharmaceutical') }}" class="btn btn-outline">The pharmaceutical division</a>
                    <a href="{{ route('account.create') }}" class="btn btn-primary">Open a pharmacy account</a>
                </div>
            </div>
        </div>
    </section>

    {{-- Heritage --}}
    <section class="wrap mt-20 md:mt-28" aria-labelledby="hist-title">
        <x-rack-tag label="Since 1891" />
        <h2 id="hist-title" class="display-2 mt-3 max-w-3xl">From a Castries coaling business to Acado St. Lucia.</h2>
        <ol class="timeline mt-10 grid gap-x-8 gap-y-8 pt-8 sm:grid-cols-2 lg:grid-cols-3" data-reveal>
            @foreach (peterco('history') as $h)
                <li class="timeline-item" style="--t: {{ $loop->index }}">
                    <x-stencil :text="$h['year']" class="text-4xl text-title" />
                    <p class="mt-2 text-ink-2">{{ $h['text'] }}</p>
                </li>
            @endforeach
        </ol>
        <a href="{{ route('about') }}" class="btn btn-outline mt-10">More about us<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
    </section>

    {{-- Careers + principals --}}
    {{-- Careers: a full band in Acado style, with the live count of open vacancies --}}
    <section class="careers-band relative isolate mt-20 overflow-hidden bg-navy-950 text-white on-dark md:mt-28" aria-labelledby="careers-title" data-inview>
        <div class="hero-photo absolute inset-0 -z-10"><x-photo key="acado-careers" alt="" sizes="100vw" class="h-full w-full !bg-navy-950" position="70% 30%" /></div>
        <div class="careers-scrim absolute inset-0 -z-10" aria-hidden="true"></div>
        <x-acado-mark variant="line" class="careers-orbit" />
        <div class="wrap grid gap-10 py-16 md:py-24 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
            <div data-reveal>
                <x-rack-tag label="Careers" />
                <h2 id="careers-title" class="display-2 mt-3 max-w-2xl text-white">Work in the warehouse, on the road or in sales.</h2>
                <p class="lead mt-4 max-w-[56ch] text-white/90">Warehouse, delivery, merchandising, sales and office roles at Cul De Sac and across the island. HR reads every application.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    @if ($openVacancies)
                        <a href="{{ route('careers.index') }}#vacancies" class="btn btn-yellow">See {{ $openVacancies }} open {{ \Illuminate\Support\Str::plural('vacancy', $openVacancies) }}<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                        <a href="{{ route('careers.apply') }}" class="btn btn-on-dark">Send your CV</a>
                    @else
                        <a href="{{ route('careers.apply') }}" class="btn btn-yellow">Send your CV<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                        <a href="{{ route('careers.index') }}" class="btn btn-on-dark">About working here</a>
                    @endif
                </div>
            </div>
            <a href="{{ $openVacancies ? route('careers.index').'#vacancies' : route('careers.apply') }}" class="careers-count" data-reveal style="--reveal-delay: 150ms">
                @if ($openVacancies)
                    <span class="font-display num text-6xl font-bold text-yellow md:text-7xl" data-count="{{ $openVacancies }}" style="min-width: {{ strlen((string) $openVacancies) }}ch">{{ $openVacancies }}</span>
                    <span class="mt-2 block font-display text-xl font-semibold text-white">open {{ \Illuminate\Support\Str::plural('vacancy', $openVacancies) }} right now</span>
                    <span class="mt-1 block text-white/85">Or send a general application: HR keeps CVs on file for twelve months.</span>
                @else
                    <span class="font-display block text-3xl font-bold text-yellow">Send your CV</span>
                    <span class="mt-2 block text-white/85">No open vacancies today. HR keeps general applications on file for the next opening.</span>
                @endif
            </a>
        </div>
    </section>

    {{-- Principals --}}
    <section class="wrap mt-20 md:mt-28" aria-label="Principals">
        <article class="card-link carton lift grid overflow-hidden md:grid-cols-[0.9fr_1.1fr]">
            <div class="card-img photo aspect-[16/8] md:aspect-auto"><x-photo key="castries-container-ship" alt="" sizes="(min-width: 768px) 45vw, 100vw" class="h-full w-full" /></div>
            <div class="p-6 md:p-10">
                <x-rack-tag label="Principals" />
                <h2 class="display-3 mt-3"><a href="{{ route('principals') }}" class="stretch">Bring your brand to St Lucia.</a></h2>
                <p class="mt-2 text-ink-2">Brand owners outside St Lucia can tell us about their range, markets and plans. Our management team replies by email.</p>
                <p class="mt-5 inline-flex items-center gap-2 font-semibold text-link">Make a principal enquiry<x-icon name="arrow-right" class="h-4 w-4" /></p>
            </div>
        </article>
    </section>
</x-layouts.site>
