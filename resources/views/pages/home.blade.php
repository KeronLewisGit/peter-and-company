@php
    $divisions = (array) peterco('divisions');
    $brandCount = collect($divisions)->sum(fn (array $d) => count($d['brands']));
    $phone = peterco('phone');
@endphp
<x-layouts.site>
    {{-- Home hero: a kraft band with the headline, a manifest of sourced facts and a stacked pair of photos --}}
    <section class="kraft relative" data-hero="home">
        <div class="wrap grid gap-10 pb-14 pt-10 md:pt-16 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:gap-14 lg:pb-20">
            <div class="min-w-0">
                <x-rack-tag code="A-00" label="Formerly Peter & Company Distribution" />
                <h1 class="display-1 mt-6">St Lucia's shelves, <span class="text-link">stocked</span> from Cul De Sac.</h1>
                <p class="lead mt-6 text-ink-2">Acado St. Lucia distributes wines and spirits, beer and beverages, food, household and personal care, and pharmaceuticals to the island's supermarkets, minimarts, bars, hotels and pharmacies.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('catalogue') }}" class="btn btn-primary">Browse the trade catalogue<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                    <a href="{{ route('account.create') }}" class="btn btn-outline">Open a trade account</a>
                </div>
                <dl class="facts mt-10 max-w-xl border-t-2 border-ink text-[0.9375rem]">
                    <div><dt>Trading since</dt><dd class="num">1891, as Peter &amp; Company</dd></div>
                    <div><dt>Divisions</dt><dd>{{ count($divisions) }}: {{ collect($divisions)->pluck('short')->join(', ', ' and ') }}</dd></div>
                    <div><dt>Brands on our division pages</dt><dd class="num">{{ $brandCount }}, including Carib, El Dorado and Hennessy</dd></div>
                    <div><dt>Part of</dt><dd>Acado Limited, the Agostini's and Goddard Enterprises joint venture</dd></div>
                </dl>
            </div>
            <div class="relative mx-auto w-full max-w-xl pb-6 pr-4 lg:max-w-none">
                <div class="hero-photo framed photo aspect-[4/3]">
                    <x-photo key="castries-city-port" alt="Castries, the harbour and the container port, seen from the hills" :eager="true" sizes="(min-width: 1024px) 45vw, 100vw" class="h-full w-full" />
                </div>
                <div class="framed photo absolute -bottom-2 right-0 hidden aspect-[4/3] w-[42%] sm:block">
                    <x-photo key="warehouse-racking" alt="" sizes="20vw" class="h-full w-full" />
                </div>
                <x-pallet-label code="PC-1891" line="Castries · Saint Lucia" class="absolute -left-2 top-5 hidden w-52 -rotate-2 sm:block lg:-left-10" />
            </div>
        </div>
        <div class="flutes absolute inset-x-0 -bottom-[10px]" aria-hidden="true"></div>
    </section>

    {{-- "I'd like to…": every trade action, one tap away --}}
    <section class="wrap mt-16 md:mt-20" aria-labelledby="todo-title">
        <h2 id="todo-title" class="kicker text-ink-3">I'd like to…</h2>
        <ul class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ([
                ['catalogue', 'Browse the catalogue', 'Every line we stock, by brand and division. No login needed.', 'list'],
                ['order.show', 'Request an order', 'Build a list and send it. The trade desk confirms price and delivery.', 'crate'],
                ['account.create', 'Open a trade account', 'Cash or credit terms, with your licence and VAT documents.', 'shield'],
                ['principals', 'Distribute my brand', 'Overseas brand owners looking for St Lucia distribution.', 'globe'],
                ['careers.index', 'Find a job', 'Warehouse, delivery, merchandising and sales roles.', 'briefcase'],
            ] as [$route, $label, $text, $icon])
                <li class="card-link carton p-5 pt-6 transition hover:-translate-y-0.5 hover:shadow-md">
                    <x-icon :name="$icon" class="h-6 w-6 text-link" />
                    <h3 class="mt-3 text-lg font-bold leading-snug"><a href="{{ route($route) }}" class="stretch">{{ $label }}</a></h3>
                    <p class="mt-1 text-[0.9375rem] text-ink-2">{{ $text }}</p>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- The four divisions --}}
    <section class="wrap mt-20 md:mt-28" aria-labelledby="div-title">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <x-rack-tag code="A-01" label="Divisions" />
                <h2 id="div-title" class="display-2 mt-4 max-w-2xl">Four divisions, one delivery.</h2>
            </div>
            <a href="{{ route('brands.index') }}" class="btn btn-outline">All {{ $brandCount }} brands<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </div>
        <div class="mt-10 grid gap-6 md:grid-cols-2">
            @foreach ($divisions as $key => $d)
                <article class="card-link group grid overflow-hidden rounded-md border-[1.5px] border-ink bg-surface sm:grid-cols-[0.9fr_1.1fr]" data-reveal style="--reveal-delay: {{ $loop->index * 80 }}ms">
                    <div class="card-img photo aspect-[16/10] sm:aspect-auto sm:min-h-full">
                        <x-photo :key="$d['photo']" alt="" sizes="(min-width: 768px) 22vw, 100vw" class="h-full w-full" />
                    </div>
                    <div class="flex flex-col p-6">
                        <div class="flex items-center gap-3">
                            <x-stencil :text="$d['code']" class="text-4xl text-ink" />
                            @if (! empty($d['restricted']))<span class="chip" data-tone="attention"><x-icon name="lock" />Licensed buyers only</span>@endif
                        </div>
                        <h3 class="display-3 mt-3"><a href="{{ route('brands.show', $key) }}" class="stretch">{{ $d['name'] }}</a></h3>
                        <p class="mt-2 text-ink-2">{{ $d['blurb'] }}</p>
                        <p class="mt-4 text-sm font-semibold text-ink-3">{{ collect($d['brands'])->take(5)->join(' · ') }} and {{ count($d['brands']) - 5 }} more</p>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- From the catalogue --}}
    @if ($featured->isNotEmpty())
        <section class="mt-20 border-y-[1.5px] border-ink bg-surface py-16 md:mt-28 md:py-20" aria-labelledby="cat-title">
            <div class="wrap">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <x-rack-tag code="A-02" label="Trade catalogue" />
                        <h2 id="cat-title" class="display-2 mt-4 max-w-2xl">Add it to your order list.</h2>
                        <p class="mt-3 max-w-[60ch] text-ink-2">Pick the cases you need and send the list. Prices, availability and your delivery day are confirmed by the trade desk, so you never pay for something that isn't on the truck.</p>
                    </div>
                    <a href="{{ route('catalogue') }}" class="btn btn-outline">All {{ $productCount }} lines<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                </div>
                <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($featured as $p)
                        <x-product-card :product="$p" :on-list="\App\Support\OrderList::items()[$p->id] ?? 0" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- How ordering works --}}
    <section class="wrap mt-20 md:mt-28" aria-labelledby="how-title">
        <div class="grid gap-12 lg:grid-cols-[0.9fr_1.1fr] lg:items-start">
            <div>
                <x-rack-tag code="A-03" label="How trade ordering works" />
                <h2 id="how-title" class="display-2 mt-4">From list to loading bay in four steps.</h2>
                <p class="mt-4 max-w-[60ch] text-ink-2">The website takes the request; your sales representative and the trade desk do what they always have. Nothing is charged online.</p>
                <a href="{{ route('order.show') }}" class="btn btn-primary mt-8">Start an order list<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
            </div>
            <ol class="grid gap-4 sm:grid-cols-2">
                @foreach ([
                    ['Build your list', 'Add cases from the catalogue, or type in anything you can\'t find. Works on any phone.'],
                    ['Send it with your account number', 'Approved trade accounts are matched straight away. No account yet? Send it anyway.'],
                    ['The trade desk calls you', 'Prices, availability, substitutions and the delivery day, confirmed by phone or WhatsApp.'],
                    ['Delivered from Cul De Sac', 'Your reference number follows the order from the warehouse to your door.'],
                ] as $i => [$h, $t])
                    <li class="carton p-5 pt-6">
                        <x-stencil :text="sprintf('%02d', $i + 1)" class="text-3xl text-link" />
                        <h3 class="mt-2 text-lg font-bold">{{ $h }}</h3>
                        <p class="mt-1 text-[0.9375rem] text-ink-2">{{ $t }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Pharmacies --}}
    <section class="wrap mt-20 md:mt-28" aria-labelledby="rx-title">
        <div class="grid overflow-hidden rounded-md border-[1.5px] border-ink bg-surface md:grid-cols-[1fr_1fr]">
            <div class="photo aspect-[16/10] md:aspect-auto"><x-photo key="pharmacy-shelves" alt="Pharmacy shelves stocked with over-the-counter medicines" sizes="(min-width: 768px) 50vw, 100vw" class="h-full w-full" /></div>
            <div class="p-7 md:p-10">
                <x-rack-tag code="RX" label="Pharmaceuticals" />
                <h2 id="rx-title" class="display-3 mt-4">For licensed pharmacies, hospitals and clinics.</h2>
                <p class="mt-3 text-ink-2">{{ $divisions['pharmaceutical']['blurb'] }} Pharmaceutical orders are accepted only from approved pharmacy and clinic accounts with a licence on file. We do not sell medicines to the public.</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('brands.show', 'pharmaceutical') }}" class="btn btn-outline">The pharmaceutical division</a>
                    <a href="{{ route('account.create') }}" class="btn btn-ink">Open a pharmacy account</a>
                </div>
            </div>
        </div>
    </section>

    {{-- Heritage --}}
    <section class="wrap mt-20 md:mt-28" aria-labelledby="hist-title">
        <x-rack-tag code="A-04" label="Since 1891" />
        <h2 id="hist-title" class="display-2 mt-4 max-w-3xl">From a Castries coaling business to Acado St. Lucia.</h2>
        <ol class="mt-10 grid gap-x-6 gap-y-8 border-t-2 border-ink pt-8 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (peterco('history') as $h)
                <li data-reveal style="--reveal-delay: {{ $loop->index * 60 }}ms">
                    <x-stencil :text="$h['year']" class="text-4xl text-ink" />
                    <p class="mt-2 text-ink-2">{{ $h['text'] }}</p>
                </li>
            @endforeach
        </ol>
        <a href="{{ route('about') }}" class="btn btn-outline mt-10">More about us<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
    </section>

    {{-- Careers + principals --}}
    <section class="wrap mt-20 grid gap-6 md:mt-28 md:grid-cols-2" aria-label="Careers and principals">
        <article class="card-link overflow-hidden rounded-md border-[1.5px] border-ink bg-surface">
            <div class="card-img photo aspect-[16/8]"><x-photo key="castries-container-ship" alt="" sizes="(min-width: 768px) 45vw, 100vw" class="h-full w-full" /></div>
            <div class="p-6">
                <x-rack-tag code="PR" label="Principals" />
                <h2 class="display-3 mt-4"><a href="{{ route('principals') }}" class="stretch">Bring your brand to St Lucia.</a></h2>
                <p class="mt-2 text-ink-2">Brand owners outside St Lucia can tell us about their range, markets and plans. Our management team replies by email.</p>
            </div>
        </article>
        <article class="card-link overflow-hidden rounded-md border-[1.5px] border-ink bg-surface">
            <div class="card-img photo aspect-[16/8]"><x-photo key="warehouse-racking" alt="" sizes="(min-width: 768px) 45vw, 100vw" class="h-full w-full" /></div>
            <div class="p-6">
                <x-rack-tag code="CV" label="Careers" />
                <h2 class="display-3 mt-4"><a href="{{ route('careers.index') }}" class="stretch">Work in the warehouse, on the road or in sales.</a></h2>
                <p class="mt-2 text-ink-2">{{ $openVacancies ? $openVacancies.' open '.\Illuminate\Support\Str::plural('vacancy', $openVacancies).' right now, and' : 'No open vacancies today, but' }} you can send a general application with your CV at any time.</p>
            </div>
        </article>
    </section>

    {{-- Closing call to action --}}
    <section class="wrap mt-20 md:mt-28">
        <div class="relative overflow-hidden rounded-md bg-crate-900 p-8 text-white on-dark md:p-12">
            <div class="grid gap-8 md:grid-cols-[1.4fr_1fr] md:items-center">
                <div>
                    <h2 class="display-2 text-white">Not a trade customer yet?</h2>
                    <p class="mt-3 max-w-[55ch] text-white/80">Apply for a cash-on-delivery or credit account online. Upload your trade licence, VAT certificate and ID, and credit control takes it from there.</p>
                </div>
                <div class="flex flex-wrap gap-3 md:justify-end">
                    <a href="{{ route('account.create') }}" class="btn bg-sky text-[#0d1b24] hover:bg-white">Apply for an account<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                    @if ($phone)<a href="{{ tel_href($phone) }}" class="btn btn-on-dark"><x-icon name="phone" class="h-4 w-4" />{{ format_phone($phone) }}</a>@endif
                </div>
            </div>
        </div>
    </section>
</x-layouts.site>
