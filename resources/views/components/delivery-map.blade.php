@php
    $map = (array) peterco('delivery_map');
    $names = (array) peterco('districts');
    $shapes = require resource_path('data/st-lucia-districts.php');
    [$hx, $hy] = $map['hub'];
    $dayNames = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];
    $daysText = function (array $days) use ($dayNames): string {
        if ($days === [1, 2, 3, 4, 5]) {
            return 'Monday to Friday';
        }

        return collect($days)->map(fn (int $d) => $dayNames[$d])->join(', ', ' and ');
    };
    // The map is cropped to the island: x 200–775, y 95–1220 of the original canvas.
    [$vx, $vy, $vw, $vh] = [200, 95, 575, 1125];
    $routes = collect($map['districts'])->map(function (array $d, string $key) use ($hx, $hy, $names, $daysText, $vx, $vy, $vw, $vh) {
        [$x, $y] = $d['at'];
        // A gentle curve: the control point sits off the midpoint, perpendicular to the route.
        $mx = ($hx + $x) / 2;
        $my = ($hy + $y) / 2;
        $len = max(1, hypot($x - $hx, $y - $hy));
        $bend = min(60, $len * 0.18);
        $cx = round($mx + ($y - $hy) / $len * $bend);
        $cy = round($my - ($x - $hx) / $len * $bend);

        return [
            'key' => $key,
            'name' => $names[$key] ?? $key,
            'days' => $daysText($d['days']),
            'd' => "M{$hx} {$hy}Q{$cx} {$cy} {$x} {$y}",
            'x' => $x,
            'y' => $y,
            'left' => round(($x - $vx) / $vw * 100, 2),
            'top' => round(($y - $vy) / $vh * 100, 2),
        ];
    })->values();
@endphp
{{-- St Lucia delivery map: districts from Wikimedia Commons (credited on /photo-credits), routes out of Cul De Sac.
     The district list is the content: it shows every district's days as text, with or without JavaScript. --}}
<div {{ $attributes->merge(['class' => 'dmap']) }} data-dmap data-reveal>
    <div class="dmap-list-wrap">
        {{ $slot }}
        <ul class="dmap-list mt-8" aria-label="Delivery days by district">
            @foreach ($routes->sortBy('name') as $r)
                <li data-district="{{ $r['key'] }}" data-days="{{ $r['days'] }}" data-left="{{ $r['left'] }}" data-top="{{ $r['top'] }}"><span class="block font-semibold text-ink">{{ $r['name'] }}</span><span class="block text-[0.9375rem] text-ink-2">{{ $r['days'] }}</span></li>
            @endforeach
        </ul>
        @if (! empty($map['placeholder']))
            <p class="mt-4 flex gap-2 text-sm text-ink-3"><span class="sample-badge flex-none">Sample schedule</span>Route days shown for the demo, to be replaced with the trade desk's own. Your delivery day is always confirmed by phone.</p>
        @endif
    </div>
    <div class="dmap-panel"><div class="dmap-figure">
        <svg viewBox="{{ $vx }} {{ $vy }} {{ $vw }} {{ $vh }}" class="dmap-svg" role="group" aria-label="Map of St Lucia's ten districts, with delivery routes from Cul De Sac">
            <g class="dmap-land">
                @foreach ($routes as $r)
                    <path d="{{ $shapes[$r['key']] }}" class="dmap-district" data-district="{{ $r['key'] }}" tabindex="0" role="img" aria-label="{{ $r['name'] }}: delivery {{ $r['days'] }}"><title>{{ $r['name'] }}: {{ $r['days'] }}</title></path>
                @endforeach
            </g>
            <g class="dmap-routes" aria-hidden="true">
                @foreach ($routes as $r)
                    <path d="{{ $r['d'] }}" pathLength="1" class="dmap-route" style="--i: {{ $loop->index }}" />
                @endforeach
                @foreach ($routes as $r)
                    <circle cx="{{ $r['x'] }}" cy="{{ $r['y'] }}" r="7" class="dmap-stop" style="--i: {{ $loop->index }}" />
                    <circle r="5" class="dmap-van" style="--i: {{ $loop->index }}; offset-path: path('{{ $r['d'] }}')" />
                @endforeach
                <g class="dmap-hub" transform="translate({{ $hx }} {{ $hy }})">
                    <circle r="26" class="dmap-pulse" />
                    <circle r="15" class="dmap-hub-dot" />
                </g>
            </g>
        </svg>
        <div class="dmap-card" data-dmap-card aria-hidden="true"><p class="dmap-card-name"></p><p class="dmap-card-days"></p></div>
        <p class="dmap-hub-label" style="left: {{ round(($hx - $vx) / $vw * 100, 2) }}%; top: {{ round(($hy - $vy) / $vh * 100, 2) }}%" aria-hidden="true">Cul De Sac</p>
    </div></div>
</div>
