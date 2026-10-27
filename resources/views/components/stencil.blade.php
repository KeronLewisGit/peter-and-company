@props(['text'])
{{-- Stencilled numerals: decorative; the real value is read out by the sr-only copy. --}}
<span {{ $attributes->merge(['class' => 'stencil']) }}><span class="sr-only">{{ $text }}</span>@foreach (mb_str_split((string) $text) as $ch)<span aria-hidden="true">{{ $ch }}</span>@endforeach</span>
