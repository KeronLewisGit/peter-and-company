@props(['tone' => 'auto', 'small' => false])
{{--
    Acado St. Lucia's own logo (the files acadostlucia.com uses), as an image: the black
    lettering on light surfaces, the white version on dark bands and in dark mode.
--}}
@php($h = $small ? 'h-8' : 'h-9 sm:h-10')
<span {{ $attributes->merge(['class' => 'inline-flex items-center']) }}>
    @if ($tone === 'light')
        <img src="{{ asset('img/brand/acado-st-lucia-white.png') }}" alt="Acado St. Lucia" width="800" height="205" class="{{ $h }} w-auto">
    @else
        <img src="{{ asset('img/brand/acado-st-lucia.png') }}" alt="Acado St. Lucia" width="800" height="205" class="dark-hide {{ $h }} w-auto">
        <img src="{{ asset('img/brand/acado-st-lucia-white.png') }}" alt="Acado St. Lucia" width="800" height="205" class="dark-show {{ $h }} w-auto">
    @endif
</span>
