@php($divisions = (array) peterco('divisions'))
<x-layouts.site title="Our brands" description="The brands Acado St. Lucia distributes, by division: wines, spirits and tobacco; brewery, beverages and snacks; food, household and personal care; and pharmaceuticals.">
    <x-page-hero title="Our brands, by division." lead="The brands listed on Acado St. Lucia's division pages, from Carib and El Dorado to Colgate and Panadol. Choose a division to see the full list." code="B-01" label="Brand portfolio" photo="warehouse-racking" alt="A distribution warehouse with tall pallet racking and stacked cartons" label-code="{{ collect($divisions)->sum(fn ($d) => count($d['brands'])) }} brands">
        <nav class="tag-row mt-7" aria-label="Divisions">
            @foreach ($divisions as $key => $d)
                <a href="#{{ $key }}" class="tag">{{ $d['short'] }} <span class="count">{{ count($d['brands']) }}</span></a>
            @endforeach
        </nav>
    </x-page-hero>

    @foreach ($divisions as $key => $d)
        <section id="{{ $key }}" class="wrap mt-16 md:mt-20" aria-labelledby="h-{{ $key }}">
            <div class="grid gap-8 border-t-2 border-ink pt-8 lg:grid-cols-[0.8fr_1.2fr]">
                <div>
                    <x-stencil :text="$d['code']" class="text-5xl" />
                    <h2 id="h-{{ $key }}" class="display-3 mt-3">{{ $d['name'] }}</h2>
                    <p class="mt-3 text-ink-2">{{ $d['blurb'] }}</p>
                    <p class="mt-3 text-[0.9375rem] text-ink-3">{{ $d['note'] }}</p>
                    <div class="mt-6 flex flex-wrap gap-3">
                        <a href="{{ route('brands.show', $key) }}" class="btn btn-outline btn-sm">About this division</a>
                        @if (empty($d['restricted']))
                            <a href="{{ route('catalogue', ['division' => $key]) }}" class="btn btn-primary btn-sm">Catalogue ({{ $counts[$key] ?? 0 }})</a>
                        @else
                            <span class="chip self-center" data-tone="attention"><x-icon name="lock" />Licensed pharmacies and clinics only</span>
                        @endif
                    </div>
                </div>
                <ul class="brand-list text-[0.9375rem]">
                    @foreach ($d['brands'] as $b)<li>{{ $b }}</li>@endforeach
                </ul>
            </div>
        </section>
    @endforeach

    <section class="wrap mt-16">
        <p class="measure text-sm text-ink-3">Brand names are shown as text and belong to their owners. The list is taken from Acado St. Lucia's own division pages and will be confirmed with the company before launch.</p>
    </section>
</x-layouts.site>
