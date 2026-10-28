@props(['code' => null, 'label'])
{{-- Eyebrow label above a section heading. ($code is kept for older call sites and not shown.) --}}
<p {{ $attributes->merge(['class' => 'rack-tag']) }}>{{ $label }}</p>
