@props(['title', 'lead' => null, 'code' => 'A-01', 'label' => 'Acado St. Lucia', 'photo' => null, 'position' => 'center', 'alt' => '', 'labelCode' => null, 'labelLine' => null])
{{--
    The one hero every inner page uses, so titles start at the same height and left edge
    site-wide: a kraft carton band with a rack label, the title and lead on the left, and a
    framed photo with a pallet label on the right (under the text on phones). The band ends
    in a corrugated edge. Extra content (breadcrumbs, actions, filters) goes in the slot.
--}}
<section {{ $attributes->merge(['class' => 'kraft relative']) }} data-hero="page">
    <div class="wrap grid gap-8 pb-10 pt-9 md:pb-14 md:pt-14 lg:grid-cols-[1.15fr_0.85fr] lg:items-center lg:gap-14">
        <div class="min-w-0">
            <x-rack-tag :code="$code" :label="$label" />
            <h1 class="display-2 mt-5 max-w-3xl">{{ $title }}</h1>
            @if ($lead)<p class="lead mt-4 text-ink-2 md:mt-5">{{ $lead }}</p>@endif
            {{ $slot }}
        </div>
        @if ($photo)
            <div class="relative mr-3 mb-3 lg:mr-0">
                <div class="hero-photo framed photo aspect-[16/9] lg:aspect-[4/3]">
                    <x-photo :key="$photo" :alt="$alt" :eager="true" sizes="(min-width: 1024px) 40vw, 100vw" class="h-full w-full" :position="$position" />
                </div>
                <x-pallet-label :code="$labelCode ?? $code" :line="$labelLine ?? $label" class="absolute -bottom-4 left-3 hidden w-56 sm:block lg:-left-8" />
            </div>
        @endif
    </div>
    <div class="flutes absolute inset-x-0 -bottom-[10px]" aria-hidden="true"></div>
</section>
