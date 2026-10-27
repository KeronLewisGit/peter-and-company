<x-layouts.admin title="Catalogue">
    <x-slot:actions><a href="{{ route('admin.products.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="h-4 w-4" />Add a line</a></x-slot:actions>
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div class="flex-1"><label for="pq" class="mb-1 block text-xs font-semibold text-ink-3">Search</label><input id="pq" name="q" value="{{ $filters['q'] }}" class="a-input w-full min-w-48" placeholder="Name, brand or product code"></div>
        <div><label for="pd" class="mb-1 block text-xs font-semibold text-ink-3">Division</label>
            <select id="pd" name="division" class="a-input"><option value="">All</option>@foreach (\App\Models\Product::publicDivisions() as $k => $d)<option value="{{ $k }}" @selected($filters['division'] === $k)>{{ $d['short'] }}</option>@endforeach</select></div>
        <button class="a-btn a-btn-primary">Filter</button>
    </form>
    <p class="mt-3 text-sm text-ink-3">No prices or stock levels are kept here on purpose: the trade desk confirms both on every order. The pharmaceutical range is never listed publicly.</p>
    <div class="a-card table-box mt-4">
        <table class="data-table">
            <thead><tr><th>Line</th><th>Division</th><th>Case</th><th>Code</th><th>Shown</th><th><span class="sr-only">Edit</span></th></tr></thead>
            <tbody>
                @forelse ($products as $p)
                    <tr>
                        <td><p class="text-sm text-ink-3">{{ $p->brand }}</p><p class="font-semibold">{{ $p->name }}</p></td>
                        <td class="whitespace-nowrap">{{ $p->divisionName() }}</td>
                        <td class="num whitespace-nowrap">{{ $p->pack_size ?? '—' }}</td>
                        <td class="num">{{ $p->sku ?? '—' }}</td>
                        <td class="whitespace-nowrap">
                            @if ($p->published)<x-chip tone="good" icon="check">Published</x-chip>@else<x-chip tone="muted" icon="eye">Hidden</x-chip>@endif
                            @if ($p->featured)<x-chip tone="neutral" icon="star">Home page</x-chip>@endif
                            @if ($p->is_demo)<span class="sample-badge">Sample</span>@endif
                        </td>
                        <td class="r"><a href="{{ route('admin.products.edit', $p) }}" class="a-btn a-btn-ghost">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-ink-3">No lines match.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $products->links() }}</div>
</x-layouts.admin>
