@props(['product', 'onList' => 0])
@php
    /** @var \App\Models\Product $product */
    $division = peterco('divisions.'.$product->division);
@endphp
{{-- A catalogue line drawn as the end of a carton: shipping marks on top, the brand stamped on kraft. --}}
<article id="p-{{ $product->slug }}" {{ $attributes->merge(['class' => 'case']) }}>
    <div class="case-marks"><span class="div">{{ $division['code'] ?? '··' }}</span><span>{{ $product->category ?? $division['short'] }}</span><span class="up" aria-hidden="true"><x-icon name="arrow-up" class="h-3 w-3" /><x-icon name="arrow-up" class="h-3 w-3" /></span></div>
    @if ($product->photoUrl())
        <div class="photo aspect-[4/3] bg-surface-2"><img src="{{ $product->photoUrl() }}" alt="" loading="lazy" decoding="async" class="!object-contain p-4"></div>
    @else
        <div class="case-art" aria-hidden="true"><span class="brand-stamp">{{ $product->brand }}</span></div>
    @endif
    <div class="flex flex-1 flex-col p-4">
        <p class="text-sm font-semibold text-ink-3">{{ $product->brand }}</p>
        <h3 class="mt-0.5 text-lg font-bold leading-snug">{{ $product->name }}</h3>
        @if ($product->pack_size)<p class="num mt-1 text-[0.9375rem] text-ink-2">Case: {{ $product->pack_size }}</p>@endif
        @if ($product->is_demo)<p class="mt-2"><span class="sample-badge">Sample listing</span></p>@endif
        <form method="POST" action="{{ route('order.add') }}" class="mt-auto flex items-end gap-2 pt-4">
            @csrf
            <input type="hidden" name="product" value="{{ $product->slug }}">
            <div><label for="c-{{ $product->id }}" class="mb-1 block text-xs font-semibold text-ink-3">Cases</label>
                <input id="c-{{ $product->id }}" name="cases" type="number" inputmode="numeric" min="1" max="{{ \App\Support\OrderList::MAX_CASES }}" value="1" class="input qty !min-h-12 !w-[4.5rem] !px-2"></div>
            <button class="btn btn-ink btn-sm flex-1 !min-h-12"><x-icon name="plus" class="h-4 w-4" />{{ $onList ? 'Add more' : 'Add to order' }}</button>
        </form>
        @if ($onList)<p class="mt-2 text-sm font-semibold text-link"><x-icon name="check" class="inline h-4 w-4" /> {{ $onList }} {{ \Illuminate\Support\Str::plural('case', $onList) }} on your list</p>@endif
    </div>
</article>
