@php
    $qs = fn (array $extra) => array_filter(array_merge($filters, $extra), fn ($v) => $v !== null && $v !== '');
@endphp
<x-layouts.admin title="Requests inbox">
    <x-slot:actions>
        <a href="{{ route('admin.leads.export', $qs([])) }}" class="a-btn a-btn-ghost"><x-icon name="download" class="h-4 w-4" />Export CSV</a>
    </x-slot:actions>

    <nav class="tag-row !mx-0 !px-0" aria-label="Status">
        <a href="{{ route('admin.leads.index', $qs(['status' => null])) }}" class="tag" @if(! $filters['status']) aria-current="true" @endif>All <span class="count">{{ $counts->sum() }}</span></a>
        <a href="{{ route('admin.leads.index', $qs(['status' => 'open'])) }}" class="tag" @if($filters['status'] === 'open') aria-current="true" @endif>Open <span class="count">{{ $openCount }}</span></a>
        @foreach ($statusTabs as $s)
            <a href="{{ route('admin.leads.index', $qs(['status' => $s->value])) }}" class="tag" @if($filters['status'] === $s->value) aria-current="true" @endif>{{ $s->label() }} <span class="count">{{ $counts[$s->value] ?? 0 }}</span></a>
        @endforeach
    </nav>

    <form method="GET" class="mt-4 flex flex-wrap items-end gap-3" x-data @change="$el.requestSubmit()">
        @if ($filters['status'])<input type="hidden" name="status" value="{{ $filters['status'] }}">@endif
        <div><label for="type" class="mb-1 block text-xs font-semibold text-ink-3">Type</label>
            <select id="type" name="type" class="a-input"><option value="">All types</option>@foreach ($types as $t)<option value="{{ $t->value }}" @selected($filters['type'] === $t->value)>{{ $t->label() }}</option>@endforeach</select></div>
        <div><label for="assigned" class="mb-1 block text-xs font-semibold text-ink-3">Assigned to</label>
            <select id="assigned" name="assigned" class="a-input"><option value="">Anyone</option><option value="{{ auth()->id() }}" @selected($filters['assigned'] === (string) auth()->id())>Me</option><option value="none" @selected($filters['assigned'] === 'none')>Nobody</option>@foreach ($staff as $u)<option value="{{ $u->id }}" @selected($filters['assigned'] === (string) $u->id)>{{ $u->name }}</option>@endforeach</select></div>
        <label class="flex min-h-11 items-center gap-2 text-sm font-semibold"><input type="checkbox" name="overdue" value="1" class="h-4 w-4" @checked($filters['overdue'])>Past response target</label>
        <div class="flex-1"><label for="q2" class="mb-1 block text-xs font-semibold text-ink-3">Search</label><input id="q2" name="q" value="{{ $filters['q'] }}" class="a-input w-full min-w-48" placeholder="Name, business, phone, email or reference"></div>
        <button class="a-btn a-btn-primary">Filter</button>
        @if (array_filter($filters))<a href="{{ route('admin.leads.index') }}" class="a-btn a-btn-ghost">Clear</a>@endif
    </form>

    <div class="a-card relative mt-5 overflow-x-auto">
        <table class="data-table">
            <thead><tr><th>Received</th><th>Customer</th><th>Type</th><th>About</th><th>Delivery</th><th>Status</th><th>Assigned</th></tr></thead>
            <tbody>
                @forelse ($leads as $l)
                    <tr class="relative">
                        <td class="whitespace-nowrap">
                            <p class="num">{{ $l->created_at->format('j M, H:i') }}</p>
                            @if ($l->isOverdue())<p class="chip mt-1 num" data-tone="critical"><x-icon name="clock" />{{ $l->created_at->diffForHumans(null, true, true) }} waiting</p>@endif
                        </td>
                        <td><a href="{{ route('admin.leads.show', $l) }}" class="whitespace-nowrap font-semibold underline-offset-2 hover:underline after:absolute after:inset-0">{{ $l->business_name ?? $l->name }}</a><p class="num whitespace-nowrap text-sm text-ink-3">@if($l->business_name){{ $l->name }}<br>@endif{{ $l->reference }}<br>{{ format_phone($l->phone) }}</p></td>
                        <td class="whitespace-nowrap"><span class="inline-flex items-center gap-1.5"><x-icon :name="$l->type->icon()" class="h-4 w-4 text-ink-3" />{{ $l->type->label() }}</span></td>
                        <td class="max-w-56 truncate">{{ $l->subject() ?? '—' }}</td>
                        <td class="num whitespace-nowrap">{{ $l->delivery_date ? $l->delivery_date->format('D j M') : '—' }}</td>
                        <td><x-status :status="$l->status" /></td>
                        <td class="whitespace-nowrap">{{ $l->assignee->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-ink-3">No requests match.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-5">{{ $leads->links() }}</div>
</x-layouts.admin>
