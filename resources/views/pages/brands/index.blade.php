@php($divisions = (array) peterco('divisions'))
<x-layouts.site title="Our brands" description="The brands Acado St. Lucia distributes, by division: wines, spirits and tobacco; brewery, beverages and snacks; food, household and personal care; and pharmaceuticals.">
    <x-page-hero title="Our brands, by division." lead="The brands on Acado St. Lucia's division pages, from Carib and El Dorado to Colgate and Panadol. Choose a division to see its page, or browse the logos below." label="Featured brands" photo="store-vieux-fort" alt="A staff member stocking juice shelves at PCD Food Mart, Vieux Fort" position="center 30%">
        <nav class="tag-row mt-7" aria-label="Divisions">
            @foreach ($divisions as $key => $d)
                <a href="#{{ $key }}" class="tag">{{ $d['short'] }} <span class="count">{{ count($d['brands']) }}</span></a>
            @endforeach
        </nav>
    </x-page-hero>

    @foreach ($divisions as $key => $d)
        <section id="{{ $key }}" @class(['py-14 md:py-20', 'band-grey' => $loop->even]) aria-labelledby="h-{{ $key }}">
            <div class="wrap">
                <div class="grid gap-8 lg:grid-cols-[0.7fr_1.3fr] lg:items-center">
                    <div class="flex items-center gap-5">
                        <img src="{{ asset('img/portfolio/'.$key.'.webp') }}" alt="" width="250" height="250" loading="lazy" class="h-24 w-24 flex-none rounded-full bg-white object-contain p-1 shadow-md sm:h-28 sm:w-28">
                        <div>
                            <p class="kicker text-primary">{{ count($d['brands']) }} brands</p>
                            <h2 id="h-{{ $key }}" class="display-3 mt-1">{{ $d['name'] }}</h2>
                        </div>
                    </div>
                    <div>
                        <p class="text-ink-2">{{ $d['blurb'] }}</p>
                        <p class="mt-2 text-[0.9375rem] text-ink-3">{{ $d['note'] }}</p>
                        <div class="mt-5 flex flex-wrap gap-3">
                            <a href="{{ route('brands.show', $key) }}" class="btn btn-outline btn-sm">About this division</a>
                            @if (empty($d['restricted']))
                                <a href="{{ route('catalogue', ['division' => $key]) }}" class="btn btn-primary btn-sm">Catalogue ({{ $counts[$key] ?? 0 }})</a>
                            @else
                                <span class="chip self-center" data-tone="attention"><x-icon name="lock" />Licensed pharmacies and clinics only</span>
                            @endif
                        </div>
                    </div>
                </div>
                <x-brand-grid :brands="$d['brands']" :dense="true" class="mt-10" />
            </div>
        </section>
    @endforeach

    <section class="wrap mt-14">
        <p class="measure text-sm text-ink-3">Brand logos as published on acadostlucia.com; trademarks of their owners. The list is taken from Acado St. Lucia's own division pages and will be confirmed with the company before launch.</p>
    </section>
</x-layouts.site>
