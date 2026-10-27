<x-layouts.admin title="Vacancies">
    <x-slot:actions><a href="{{ route('admin.vacancies.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="h-4 w-4" />Add a vacancy</a></x-slot:actions>
    <div class="a-card table-box">
        <table class="data-table">
            <thead><tr><th>Vacancy</th><th>Department</th><th>Closes</th><th class="r">Applications</th><th>Shown</th><th><span class="sr-only">Edit</span></th></tr></thead>
            <tbody>
                @forelse ($vacancies as $v)
                    <tr>
                        <td><p class="font-semibold">{{ $v->title }}</p><p class="text-sm text-ink-3">{{ $v->location }} · {{ $v->employment_type }}</p></td>
                        <td>{{ $v->department }}</td>
                        <td class="num whitespace-nowrap">{{ $v->closes_on?->format('j M Y') ?? 'Open' }}</td>
                        <td class="r"><a class="text-link underline" href="{{ route('admin.leads.index', ['type' => 'application', 'q' => '']) }}">{{ $v->applications_count }}</a></td>
                        <td class="whitespace-nowrap">@if ($v->isOpen())<x-chip tone="good" icon="check">Open</x-chip>@elseif ($v->published)<x-chip tone="muted" icon="clock">Closed</x-chip>@else<x-chip tone="muted" icon="eye">Hidden</x-chip>@endif @if($v->is_demo)<span class="sample-badge">Sample</span>@endif</td>
                        <td class="r"><a href="{{ route('admin.vacancies.edit', $v) }}" class="a-btn a-btn-ghost">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-ink-3">No vacancies yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
