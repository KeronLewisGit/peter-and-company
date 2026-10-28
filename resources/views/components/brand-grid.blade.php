@props(['brands', 'dense' => false])
@php
    // One tile per logo (Acqua Panna and S.Pellegrino share one, as on acadostlucia.com), with
    // the brand names as a caption so the portfolio stays readable and searchable as text.
    $tiles = collect($brands)->groupBy(fn (string $b) => brand_logo($b) ?? 'text:'.$b);
@endphp
<ul {{ $attributes->class(['logo-grid', 'dense' => $dense]) }}>
    @foreach ($tiles as $logo => $names)
        <li><figure class="logo-card">
            @if (! str_starts_with($logo, 'text:'))
                <div class="logo-tile"><img src="{{ $logo }}" alt="" width="320" height="320" loading="lazy" decoding="async"></div>
            @else
                <div class="text-tile" aria-hidden="true">{{ $names->first() }}</div>
            @endif
            <figcaption>{{ $names->join(' and ') }}</figcaption>
        </figure></li>
    @endforeach
</ul>
