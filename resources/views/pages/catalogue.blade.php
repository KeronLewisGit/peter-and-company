@php
    $qs = fn (array $extra) => array_filter(array_merge($filters, $extra), fn ($v) => $v !== null && $v !== '');
@endphp
<x-layouts.site title="Trade catalogue" description="The Acado St. Lucia trade catalogue: wines and spirits, beer and beverages, food, household and personal care. Add cases to an order list and send it to the trade desk.">
    <x-page-hero title="Trade catalogue" lead="Every line by brand and division. Add the cases you need to your order list; the trade desk confirms prices, availability and delivery." code="C-01" label="Catalogue" photo="personal-care-shelf" alt="A supermarket aisle stocked with toothpaste and personal care products" :label-code="$products->total().' lines'" label-line="No prices online">
        <form method="GET" action="{{ route('catalogue') }}" class="mt-7 flex max-w-xl gap-2" role="search">
            @if ($filters['division'])<input type="hidden" name="division" value="{{ $filters['division'] }}">@endif
            <label for="cat-q" class="sr-only">Search the catalogue</label>
            <input id="cat-q" name="q" value="{{ $filters['q'] }}" class="input" placeholder="Brand, product or category" maxlength="80">
            <button class="btn btn-ink flex-none"><x-icon name="search" class="h-4 w-4" /><span class="max-sm:sr-only">Search</span></button>
        </form>
    </x-page-hero>

    <section class="wrap mt-12 md:mt-16">
        <nav class="tag-row" aria-label="Divisions">
            <a href="{{ route('catalogue', $qs(['division' => null, 'brand' => null])) }}" class="tag" @if(! $filters['division']) aria-current="true" @endif>All divisions <span class="count">{{ $counts->sum() }}</span></a>
            @foreach ($divisions as $key => $d)
                <a href="{{ route('catalogue', $qs(['division' => $key, 'brand' => null])) }}" class="tag" @if($filters['division'] === $key) aria-current="true" @endif>{{ $d['short'] }} <span class="count">{{ $counts[$key] ?? 0 }}</span></a>
            @endforeach
        </nav>

        <div class="mt-8 grid gap-10 lg:grid-cols-[15rem_1fr]">
            <aside>
                <form method="GET" action="{{ route('catalogue') }}" class="lg:sticky lg:top-28">
                    @if ($filters['division'])<input type="hidden" name="division" value="{{ $filters['division'] }}">@endif
                    @if ($filters['q'])<input type="hidden" name="q" value="{{ $filters['q'] }}">@endif
                    <label for="brand" class="label">Brand</label>
                    <div class="flex gap-2 lg:flex-col">
                        <select id="brand" name="brand" class="input" onchange="this.form.requestSubmit()">
                            <option value="">All brands ({{ $brands->count() }})</option>
                            @foreach ($brands as $b)<option value="{{ $b }}" @selected($filters['brand'] === $b)>{{ $b }}</option>@endforeach
                        </select>
                        <button class="btn btn-outline btn-sm flex-none js:hidden">Show</button>
                    </div>
                    <div class="mt-6 hidden lg:block">
                        <div class="carton p-4 pt-5 text-[0.9375rem]">
                            <p class="font-bold">Pharmacies and clinics</p>
                            <p class="mt-1 text-ink-2">The pharmaceutical range is not listed here. <a class="text-link" href="{{ route('brands.show', 'pharmaceutical') }}">How licensed buyers order</a>.</p>
                        </div>
                    </div>
                </form>
            </aside>

            <div class="min-w-0">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <p class="num text-ink-2" role="status">{{ $products->total() }} {{ \Illuminate\Support\Str::plural('line', $products->total()) }}@if($filters['brand']) from <strong>{{ $filters['brand'] }}</strong>@endif @if($filters['q']) matching "<strong>{{ $filters['q'] }}</strong>"@endif</p>
                    @if (array_filter($filters))<a href="{{ route('catalogue') }}" class="text-link inline-flex min-h-11 items-center text-sm font-semibold underline underline-offset-2">Clear filters</a>@endif
                </div>
                @if ($products->isEmpty())
                    <div class="carton mt-6 p-8 pt-9">
                        <p class="text-lg font-bold">Nothing matches that search.</p>
                        <p class="mt-2 text-ink-2">Try a brand name, or type what you need under "Other items" on your order list and the trade desk will check.</p>
                        <a href="{{ route('order.show') }}" class="btn btn-primary mt-5">Go to the order list</a>
                    </div>
                @else
                    <div class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($products as $p)<x-product-card :product="$p" :on-list="$onList[$p->id] ?? 0" />@endforeach
                    </div>
                    <div class="mt-10">{{ $products->links() }}</div>
                @endif
                <p class="mt-10 text-sm text-ink-3">Lines marked "Sample listing" are examples for this preview, using brands the company distributes. The real range, pack sizes and product codes come from the trade desk.</p>
            </div>
        </div>
    </section>
</x-layouts.site>
