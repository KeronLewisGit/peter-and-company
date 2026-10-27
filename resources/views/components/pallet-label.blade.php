@props(['code', 'line' => null])
{{-- A shipping label as stuck on a wrapped pallet. Decorative: hidden from screen readers. --}}
<div {{ $attributes->merge(['class' => 'pallet-label']) }} aria-hidden="true">
    <div class="row"><span>Ship from</span><span>Cul De Sac · LC</span></div>
    <p class="big">{{ $code }}</p>
    @if ($line)<div class="row !border-b-0 !border-t-[1.5px] !border-t-current !pb-1"><span>{{ $line }}</span><span class="inline-flex items-center gap-0.5"><x-icon name="arrow-up" class="h-3.5 w-3.5" /><x-icon name="arrow-up" class="h-3.5 w-3.5" /></span></div>@endif
    <div class="barcode"></div>
</div>
