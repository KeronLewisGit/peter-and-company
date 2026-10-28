@props(['variant' => 'solid'])
{{-- The Acado mark (four leaves turning about the centre), drawn as SVG so its leaves can assemble and orbit.
     Decorative: the logo itself is the image in the header. --}}
<svg viewBox="0 0 820 820" {{ $attributes->merge(['class' => 'acado-mark mark-'.$variant]) }} aria-hidden="true" focusable="false">
    @if ($variant === 'solid')
        <defs><linearGradient id="mk-g-{{ $id = substr(md5((string) $attributes->get('class')), 0, 6) }}" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#f26921" /><stop offset="1" stop-color="#e2680e" /></linearGradient></defs>
    @endif
    <g class="mk-spin">
        @foreach ([0, 90, 180, 270] as $k => $deg)
            <g transform="rotate({{ $deg }} 410 410)">
                <path class="mk-leaf" style="--k: {{ $k }}" d="M155 128L262 22C290-4 335-4 362 22L588 213C500 300 330 310 240 215Z" @if ($variant === 'solid') fill="url(#mk-g-{{ $id }})" @endif />
            </g>
        @endforeach
    </g>
</svg>
