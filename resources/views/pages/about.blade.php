<x-layouts.site title="About us" description="Acado St. Lucia, formerly Peter & Company Distribution: a St Lucian distributor with roots back to 1891, part of Acado Limited.">
    <x-page-hero title="Since 1891, from Castries to Cul De Sac." lead="Acado St. Lucia Limited, formerly Peter & Company Distribution, is one of St Lucia's largest distributors of fast-moving consumer goods, and part of Acado Limited, the Agostini's and Goddard Enterprises joint venture." code="A-01" label="About us" photo="cul-de-sac-valley" alt="The Cul de Sac valley outside Castries, St Lucia" label-code="EST. 1891" label-line="Castries · Saint Lucia" />

    <section class="wrap mt-14 grid gap-12 md:mt-20 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="prose-copy measure text-ink-2">
            <h2 class="!mt-0 text-ink">Who we are</h2>
            <p>The company began in 1891, when William Peter founded Peter &amp; Company in Castries as a coaling business. In 1961 J. N. Goddard &amp; Sons of Barbados took a majority stake, and over the following decades the company grew into food and beverage, building materials, motor dealerships and its own shops.</p>
            <p>In 2015 Peter &amp; Company bought Bryden &amp; Partners (St. Lucia) and refocused on distributing fast-moving consumer goods. That year Caribbean Distribution Partners, the joint venture of Agostini's Limited of Trinidad and Tobago and Goddard Enterprises of Barbados, bought the company, and the two businesses merged as Peter &amp; Company Distribution.</p>
            <p>In February 2025 Caribbean Distribution Partners became Acado Limited, and Peter &amp; Company Limited became Acado St. Lucia Limited.</p>
            <h2 class="text-ink">Our vision</h2>
            <p>“To be the benchmark of our region by sustainably building brands that enrich lives for generations to come.”</p>
        </div>
        <div>
            <h2 class="kicker text-ink-3">Our values</h2>
            <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach (['People Centered', 'Integrity', 'Trust', 'Ownership', 'Entrepreneurial Spirit'] as $v)
                    <li class="carton p-4 pt-5 font-display text-lg font-bold">{{ $v }}</li>
                @endforeach
            </ul>
            <div class="framed photo mt-8 aspect-[16/5]"><x-photo key="cul-de-sac-terminal" alt="The fuel terminal and port at Cul de Sac Bay" sizes="(min-width: 1024px) 40vw, 100vw" class="h-full w-full" /></div>
        </div>
    </section>

    <section class="wrap mt-16 md:mt-24" aria-labelledby="time-title">
        <x-rack-tag code="A-02" label="Timeline" />
        <h2 id="time-title" class="display-2 mt-4">134 years in six dates.</h2>
        <ol class="mt-10 border-l-2 border-ink pl-6 sm:pl-10">
            @foreach (peterco('history') as $h)
                <li class="relative pb-10 last:pb-0">
                    <span class="absolute -left-[calc(1.5rem+7px)] top-2 h-3 w-3 rounded-sm bg-stripe sm:-left-[calc(2.5rem+7px)]" aria-hidden="true"></span>
                    <x-stencil :text="$h['year']" class="text-4xl" />
                    <p class="measure mt-2 text-ink-2">{{ $h['text'] }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    <section id="retail" class="wrap mt-16 md:mt-24" aria-labelledby="retail-title">
        <x-rack-tag code="A-03" label="Our retail stores" />
        <h2 id="retail-title" class="display-2 mt-4">Our own stores.</h2>
        <p class="mt-3 max-w-[60ch] text-ink-2">Alongside distribution, the company runs its own wine, spirits and food stores. Opening hours to be confirmed.</p>
        <ul class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (peterco('retail') as $s)
                <li class="carton p-5 pt-6">
                    <p class="font-bold">{{ $s['name'] }}</p>
                    <p class="text-ink-2">{{ $s['where'] }}</p>
                    <a href="{{ tel_href($s['phone']) }}" class="text-link mt-2 inline-flex min-h-11 items-center gap-2 font-semibold"><x-icon name="phone" class="h-4 w-4" />{{ format_phone($s['phone']) }}</a>
                </li>
            @endforeach
        </ul>
    </section>
</x-layouts.site>
