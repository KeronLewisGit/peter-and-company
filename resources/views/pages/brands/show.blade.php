@php($restricted = ! empty($division['restricted']))
<x-layouts.site :title="$division['name']" :description="$division['blurb']" :og-image="$division['photo']">
    <x-page-hero :title="$division['name']" :lead="$division['blurb']" :code="$division['code']" label="Division" :photo="$division['photo']" alt="" :label-code="count($division['brands']).' brands'" :label-line="$division['short']">
        <nav class="mt-6 text-sm text-ink-3" aria-label="Breadcrumb"><a href="{{ route('brands.index') }}" class="text-link underline underline-offset-2">Our brands</a> / {{ $division['short'] }}</nav>
    </x-page-hero>

    <section class="wrap mt-14 grid gap-12 md:mt-20 lg:grid-cols-[1.2fr_0.8fr]">
        <div>
            <h2 class="display-3">Brands in this division</h2>
            <ul class="brand-list mt-6 text-[0.9375rem]">
                @foreach ($division['brands'] as $b)<li>{{ $b }}</li>@endforeach
            </ul>
            <p class="mt-6 text-sm text-ink-3">{{ $division['note'] }}</p>
        </div>
        <aside class="space-y-6">
            <div class="carton p-6 pt-7">
                <h2 class="text-lg font-bold">Categories</h2>
                <ul class="mt-3 flex flex-wrap gap-2">@foreach ($division['categories'] as $c)<li class="chip" data-tone="neutral"><x-icon name="crate" />{{ $c }}</li>@endforeach</ul>
            </div>
            @if ($restricted)
                <div class="carton p-6 pt-7" role="note">
                    <h2 class="flex items-center gap-2 text-lg font-bold"><x-icon name="lock" class="h-5 w-5 text-link" />Licensed buyers only</h2>
                    <p class="mt-2 text-ink-2">The pharmaceutical range is supplied to licensed pharmacies, hospitals and clinics in St Lucia. We do not sell medicines to the public, and the range is not listed in the online catalogue.</p>
                    <ol class="mt-4 list-decimal space-y-1 pl-5 text-ink-2">
                        <li>Apply for a trade account with your pharmacy or clinic licence.</li>
                        <li>Credit control checks the licence and opens your account.</li>
                        <li>Order with your account number. The pharmaceuticals team sends you the current range and terms.</li>
                    </ol>
                    <a href="{{ route('account.create') }}" class="btn btn-primary mt-6 w-full">Open a pharmacy account</a>
                </div>
            @else
                <div class="carton p-6 pt-7">
                    <h2 class="text-lg font-bold">Order from this division</h2>
                    <p class="mt-2 text-ink-2">Browse the lines in the trade catalogue and add the cases you need to your order list.</p>
                    <a href="{{ route('catalogue', ['division' => $key]) }}" class="btn btn-primary mt-5 w-full">Open the catalogue</a>
                </div>
            @endif
        </aside>
    </section>

    @if ($products->isNotEmpty())
        <section class="wrap mt-16 md:mt-20" aria-labelledby="lines-title">
            <div class="flex flex-wrap items-end justify-between gap-4 border-t-2 border-ink pt-8">
                <h2 id="lines-title" class="display-3">From the catalogue</h2>
                <a href="{{ route('catalogue', ['division' => $key]) }}" class="text-link inline-flex min-h-11 items-center font-semibold underline underline-offset-2">See every line</a>
            </div>
            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($products as $p)<x-product-card :product="$p" :on-list="\App\Support\OrderList::items()[$p->id] ?? 0" />@endforeach
            </div>
        </section>
    @endif
</x-layouts.site>
