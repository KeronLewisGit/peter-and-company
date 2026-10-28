@props(['text'])
{{-- A large numeral (a year, a step, a count) in Montserrat. --}}
<span {{ $attributes->merge(['class' => 'stencil']) }}>{{ $text }}</span>
