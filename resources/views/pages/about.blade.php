<x-layouts.site title="Who we are" description="Acado St. Lucia, formerly Peter & Company Distribution: a St Lucian distributor with roots back to 1891, part of Acado Limited.">
    <x-page-hero title="Who we are" lead="Acado St. Lucia Limited, formerly Peter & Company Distribution, is one of St Lucia's largest distributors of fast-moving consumer goods, and part of Acado Limited, the Agostini's and Goddard Enterprises joint venture." label="About us" photo="acado-team" alt="Acado St. Lucia staff gathered at the unveiling of the new Acado sign in February 2025" position="60% 40%" />

    {{-- Acado's grey "History" band --}}
    <section class="band-grey" aria-labelledby="history-title">
        <div class="wrap grid gap-8 py-16 md:py-20 lg:grid-cols-[0.6fr_1.4fr] lg:items-center lg:gap-16">
            <h2 id="history-title" class="section-title">History</h2>
            <div class="measure space-y-4">
                <p>With roots dating back to the 1890s, Acado St. Lucia is one of the island's largest and most progressive distributors of consumer products. The company was founded in 1891 as Peter &amp; Company by William Peter, initially operating as a coaling business. In 1961, J. N. Goddard &amp; Sons of Barbados acquired a majority stake.</p>
                <p>Over time, the company expanded into food and beverage, building materials, and Nissan automotive dealerships, and developed its own retail stores: PCD Food Mart, PCD Wines &amp; Spirits and PCD Wet &amp; Cold Express.</p>
                <p>In 2015 Peter &amp; Company bought Bryden &amp; Partners (St. Lucia) and refocused on distributing fast-moving consumer goods. That year Caribbean Distribution Partners, the joint venture of Agostini's Limited of Trinidad and Tobago and Goddard Enterprises of Barbados, bought the company, and the two businesses merged as Peter &amp; Company Distribution (PCD).</p>
                <p>In February 2025 Caribbean Distribution Partners became Acado Limited, and Peter &amp; Company Limited became Acado St. Lucia Limited.</p>
            </div>
        </div>
    </section>

    <section class="wrap mt-16 md:mt-24" aria-labelledby="time-title">
        <x-rack-tag label="Timeline" />
        <h2 id="time-title" class="display-2 mt-3">{{ now()->year - (int) peterco('founded') }} years in six dates.</h2>
        <ol class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (peterco('history') as $h)
                <li class="carton relative overflow-hidden p-6" data-reveal style="--reveal-delay: {{ $loop->index * 60 }}ms">
                    <span class="absolute inset-x-0 top-0 h-1 bg-[linear-gradient(90deg,var(--orange),var(--yellow))]" aria-hidden="true"></span>
                    <x-stencil :text="$h['year']" class="text-4xl text-title" />
                    <p class="mt-3 text-ink-2">{{ $h['text'] }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Acado's "Our vision" photo band --}}
    <section class="relative isolate mt-20 overflow-hidden bg-navy-950 text-white on-dark md:mt-28" aria-labelledby="vision-title">
        <x-photo key="acado-vision" alt="" sizes="100vw" class="absolute inset-0 -z-10 h-full w-full !bg-navy-950" />
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(180deg,rgb(0_20_51/0.3),rgb(0_20_51/0.6))]" aria-hidden="true"></div>
        <div class="wrap py-24 text-center md:py-32">
            <h2 id="vision-title" class="section-title !text-white">Our vision</h2>
            <p class="font-display mx-auto mt-5 max-w-3xl text-xl font-medium leading-relaxed text-white md:text-2xl">“{{ peterco('vision') }}”</p>
        </div>
    </section>

    {{-- Acado's orange-to-yellow "Core values" band, then the values --}}
    <section aria-labelledby="values-title">
        <div class="band-orange on-orange">
            <div class="wrap py-14 md:py-20">
                <h2 id="values-title" class="section-title !text-white">Core values</h2>
            </div>
        </div>
        <ul class="wrap grid gap-x-10 gap-y-10 py-14 sm:grid-cols-2 md:py-20 lg:grid-cols-3">
            @foreach ((array) peterco('values') as $name => $text)
                <li>
                    <h3 class="font-display text-xl font-bold uppercase tracking-wide text-primary">{{ $name }}</h3>
                    <p class="mt-3 text-ink-2">{{ $text }}</p>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Our retail stores, with the photo of each store from acadostlucia.com --}}
    <section id="retail" class="band-grey py-16 md:py-24" aria-labelledby="retail-title">
        <div class="wrap">
            <x-rack-tag label="Our retail stores" />
            <h2 id="retail-title" class="display-2 mt-3">Our own stores.</h2>
            <p class="mt-3 max-w-[60ch] text-ink-2">Alongside distribution, the company runs its own wine, spirits and food stores. Opening hours to be confirmed.</p>
            <ul class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (peterco('retail') as $s)
                    <li class="carton lift overflow-hidden">
                        <x-photo :key="$s['photo']" :alt="$s['name'].', '.$s['where']" sizes="(min-width: 1024px) 30vw, (min-width: 640px) 45vw, 100vw" class="aspect-[10/9]" />
                        <div class="p-5">
                            <p class="font-display text-sm font-bold uppercase tracking-wide text-primary">{{ $s['name'] }}</p>
                            <p class="mt-1 text-ink-2">{{ $s['where'] }}</p>
                            <a href="{{ tel_href($s['phone']) }}" class="text-link mt-2 inline-flex min-h-11 items-center gap-2 font-semibold"><x-icon name="phone" class="h-4 w-4" />{{ format_phone($s['phone']) }}</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
</x-layouts.site>
