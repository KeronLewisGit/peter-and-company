@props(['code', 'label'])
<p {{ $attributes->merge(['class' => 'rack-tag']) }}><span class="code">{{ $code }}</span><span>{{ $label }}</span></p>
