@props(['rows' => [], 'divisionOf'])
{{-- Brand logos in two rows that drift in opposite directions (CSS only). Hover or keyboard focus pauses them,
     and the Pause button stops them for good. Each row's second copy exists only to make the loop seamless:
     it is hidden from assistive technology and cannot take focus. With reduced motion the rows stand still and
     wrap into a grid, and the copies are not shown. --}}
<div {{ $attributes->merge(['class' => 'marquee']) }} data-marquee>
    @foreach ($rows as $r => $brands)
        <div class="marquee-row" data-dir="{{ $r % 2 ? 'right' : 'left' }}" style="--n: {{ count($brands) }}">
            @foreach ([false, true] as $copy)
                <ul class="marquee-set" @if ($copy) aria-hidden="true" inert data-copy @else aria-label="{{ $r ? 'More brands from the division pages' : 'Featured brands' }}" @endif>
                    @foreach ($brands as $b)
                        <li><a href="{{ route('brands.show', $divisionOf($b)) }}" class="logo-tile" @if ($copy) tabindex="-1" @endif>
                            @if ($logo = brand_logo($b))<img src="{{ $logo }}" alt="{{ $copy ? '' : $b }}" width="320" height="320" loading="lazy" decoding="async">@else<span class="text-tile w-full">{{ $b }}</span>@endif
                        </a></li>
                    @endforeach
                </ul>
            @endforeach
        </div>
    @endforeach
    <div class="marquee-controls">
        <button type="button" class="btn btn-outline btn-sm" data-marquee-toggle aria-pressed="false" hidden><x-icon name="pause" class="h-4 w-4" data-when="running" /><x-icon name="play" class="h-4 w-4" data-when="paused" /><span data-when="running">Pause the logos</span><span data-when="paused">Play the logos</span></button>
    </div>
</div>
