@php
    $credits = collect(config('photo-credits'));
    [$own, $commons] = $credits->partition(fn (array $c) => $c['license_url'] === null);
@endphp
<x-layouts.site title="Photo credits" description="Sources and licences for the photographs and brand logos used on this website.">
    <x-page-hero title="Photo credits" lead="The Acado St. Lucia logo, the company photos and the brand-logo tiles are as published on acadostlucia.com. The remaining photographs are from Wikimedia Commons, used under the licences below." label="Credits" photo="marigot-bay" alt="Marigot Bay, St Lucia" />

    <section class="wrap mt-12 md:mt-16" aria-labelledby="own-title">
        <h2 id="own-title" class="display-3">Acado St. Lucia's own photos</h2>
        <p class="measure mt-2 text-ink-2">From acadostlucia.com: the rebrand launch, the vision and careers photos, the division images and the retail stores.</p>
        <ul class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($own as $key => $c)
                <li class="panel overflow-hidden">
                    <x-photo :key="$key" alt="" sizes="(min-width: 1024px) 22vw, (min-width: 640px) 45vw, 100vw" class="aspect-[3/2]" />
                    <div class="p-4 text-[0.9375rem]">
                        <p class="font-semibold text-ink">{{ $c['title'] }}</p>
                        <p class="mt-1 text-ink-2">{{ $c['license'] }} · <a class="text-link" href="{{ $c['source'] }}" rel="noopener" target="_blank">Source</a></p>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="wrap mt-16" aria-labelledby="logos-title">
        <h2 id="logos-title" class="display-3">Brand logos</h2>
        <p class="measure mt-2 text-ink-2">The {{ count(array_unique(config('brand-logos'))) }} brand-logo tiles on the brand, division and catalogue pages are the brand logos as published on acadostlucia.com (the logo grids on its four division pages); trademarks of their owners.</p>
    </section>

    @php $mapCredit = (array) peterco('delivery_map.credit'); @endphp
    <section class="wrap mt-16" aria-labelledby="map-credit-title">
        <h2 id="map-credit-title" class="display-3">Delivery map</h2>
        <p class="measure mt-2 text-ink-2">The district outlines on the home-page delivery map are traced from “{{ $mapCredit['title'] }}” by {{ $mapCredit['author'] }}, Wikimedia Commons,
            <a class="text-link" href="{{ $mapCredit['license_url'] }}" rel="license noopener" target="_blank">{{ $mapCredit['license'] }}</a> · <a class="text-link" href="{{ $mapCredit['source'] }}" rel="noopener" target="_blank">Source</a>.
            Recoloured, cropped and simplified; the derived map is shared under the same licence.</p>
    </section>

    <section class="wrap mt-16" aria-labelledby="commons-title">
        <h2 id="commons-title" class="display-3">Wikimedia Commons</h2>
        <p class="measure mt-2 text-ink-2">The harbour, port, Cul de Sac, Pitons, Marigot Bay and Castries market photos, for the pages the company has no photo for yet. They should be replaced with photos of the company's warehouse, fleet and team.</p>
        <ul class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($commons as $key => $c)
                <li class="panel overflow-hidden">
                    <x-photo :key="$key" alt="" sizes="(min-width: 1024px) 30vw, (min-width: 640px) 45vw, 100vw" class="aspect-[3/2]" />
                    <div class="p-5 text-[0.9375rem]">
                        <p class="font-semibold text-ink">{{ $c['title'] }}</p>
                        <p class="mt-1 text-ink-2">By {{ $c['author'] }}</p>
                        <p class="mt-1 text-ink-2">
                            <a class="text-link" href="{{ $c['license_url'] }}" rel="license noopener" target="_blank">{{ $c['license'] }}</a>
                            · <a class="text-link" href="{{ $c['source'] }}" rel="noopener" target="_blank">Source</a>
                        </p>
                    </div>
                </li>
            @endforeach
        </ul>
        <p class="mt-8 text-sm text-ink-3">Photos were resized and cropped for the web. CC BY-SA photos remain under their original licence.</p>
    </section>
</x-layouts.site>
