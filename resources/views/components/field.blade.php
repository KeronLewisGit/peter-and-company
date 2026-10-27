@props(['name', 'label', 'type' => 'text', 'required' => false, 'hint' => null, 'value' => null, 'optional' => false, 'kept' => null])
{{-- Label, input, hint and error wired together with ids and aria. Extra attributes go on the input.
     kept: the name of a file attached on an earlier try (UploadStash); the input is then optional. --}}
@php
    $id = 'f-'.str_replace(['.', '[', ']'], '-', $name);
    $describedBy = trim(($kept ? $id.'-kept ' : '').($hint ? $id.'-hint ' : '').($errors->has($name) ? str_replace(['.', '[', ']'], '-', $name).'-error' : ''));
    $required = $required && ! $kept;
@endphp
<div>
    <label for="{{ $id }}" class="label">{{ $label }}@if($optional) <span class="opt">(optional)</span>@endif</label>
    @if ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" @required($required) @if($errors->has($name)) aria-invalid="true" @endif @if($describedBy) aria-describedby="{{ $describedBy }}" @endif {{ $attributes->merge(['class' => 'input']) }}>{{ old($name, $value) }}</textarea>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" @required($required) @if($errors->has($name)) aria-invalid="true" @endif @if($describedBy) aria-describedby="{{ $describedBy }}" @endif {{ $attributes->merge(['class' => 'input']) }}>
    @endif
    @if ($kept)<span id="{{ $id }}-kept" class="hint flex items-start gap-1.5 font-semibold text-ink"><x-icon name="check-circle" class="mt-0.5 h-4 w-4 flex-none text-link" />Attached: {{ $kept }}. We kept it from your last try; choose a file only to replace it.</span>@endif
    @if ($hint)<span id="{{ $id }}-hint" class="hint">{{ $hint }}</span>@endif
    <x-error :for="$name" />
</div>
