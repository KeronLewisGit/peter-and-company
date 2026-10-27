<x-layouts.admin :title="$product->exists ? 'Edit '.$product->name : 'Add a catalogue line'">
    <form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" enctype="multipart/form-data" class="a-card max-w-3xl space-y-5 p-5 sm:p-6">
        @csrf @if ($product->exists) @method('PUT') @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="mb-1 block text-sm font-semibold" for="brand">Brand</label><input id="brand" name="brand" value="{{ old('brand', $product->brand) }}" class="a-input w-full" required maxlength="80"></div>
            <div><label class="mb-1 block text-sm font-semibold" for="name">Product name</label><input id="name" name="name" value="{{ old('name', $product->name) }}" class="a-input w-full" required maxlength="140"></div>
            <div><label class="mb-1 block text-sm font-semibold" for="division">Division</label>
                <select id="division" name="division" class="a-input w-full" required><option value="">Choose…</option>@foreach (\App\Models\Product::publicDivisions() as $k => $d)<option value="{{ $k }}" @selected(old('division', $product->division) === $k)>{{ $d['name'] }}</option>@endforeach</select></div>
            <div><label class="mb-1 block text-sm font-semibold" for="category">Category</label><input id="category" name="category" value="{{ old('category', $product->category) }}" class="a-input w-full" maxlength="60" list="cats">
                <datalist id="cats">@foreach (collect(\App\Models\Product::publicDivisions())->pluck('categories')->flatten()->unique() as $c)<option value="{{ $c }}">@endforeach</datalist></div>
            <div><label class="mb-1 block text-sm font-semibold" for="pack_size">Case / pack size</label><input id="pack_size" name="pack_size" value="{{ old('pack_size', $product->pack_size) }}" class="a-input w-full" maxlength="80" placeholder="24 × 275 ml"></div>
            <div><label class="mb-1 block text-sm font-semibold" for="sku">Product code</label><input id="sku" name="sku" value="{{ old('sku', $product->sku) }}" class="a-input w-full" maxlength="40"></div>
        </div>
        <div><label class="mb-1 block text-sm font-semibold" for="description">Description</label><textarea id="description" name="description" rows="3" class="a-input w-full" maxlength="2000">{{ old('description', $product->description) }}</textarea></div>
        <div>
            <label class="mb-1 block text-sm font-semibold" for="photo">Pack shot <span class="font-normal text-ink-3">(optional; JPG, PNG or WebP; use your own or the principal's supplied images)</span></label>
            @if ($product->photoUrl())<img src="{{ $product->photoUrl() }}" alt="" class="mb-2 h-24 rounded border border-line bg-surface-2 object-contain p-1">
                <label class="mb-2 flex items-center gap-2 text-sm"><input type="checkbox" name="remove_photo" value="1" class="h-4 w-4">Remove the current photo</label>@endif
            <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" class="a-input w-full">
        </div>
        <div class="flex flex-wrap gap-5">
            <label class="flex min-h-11 items-center gap-2 text-sm font-semibold"><input type="checkbox" name="published" value="1" class="h-4 w-4" @checked(old('published', $product->published))>Show in the catalogue</label>
            <label class="flex min-h-11 items-center gap-2 text-sm font-semibold"><input type="checkbox" name="featured" value="1" class="h-4 w-4" @checked(old('featured', $product->featured))>Feature on the home page</label>
        </div>
        <div class="flex flex-wrap gap-2"><button class="a-btn a-btn-primary">Save</button><a href="{{ route('admin.products.index') }}" class="a-btn a-btn-ghost">Cancel</a></div>
    </form>
    @if ($product->exists)
        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="mt-4" onsubmit="return confirm('Remove this line from the catalogue?')">@csrf @method('DELETE')<button class="a-btn a-btn-ghost text-[color:var(--critical-text)]"><x-icon name="trash" class="h-4 w-4" />Remove from catalogue</button></form>
    @endif
</x-layouts.admin>
