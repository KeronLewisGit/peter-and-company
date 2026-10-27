<x-layouts.admin :title="$vacancy->exists ? 'Edit '.$vacancy->title : 'Add a vacancy'">
    <form method="POST" action="{{ $vacancy->exists ? route('admin.vacancies.update', $vacancy) : route('admin.vacancies.store') }}" class="a-card max-w-3xl space-y-5 p-5 sm:p-6">
        @csrf @if ($vacancy->exists) @method('PUT') @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2"><label class="mb-1 block text-sm font-semibold" for="title">Job title</label><input id="title" name="title" value="{{ old('title', $vacancy->title) }}" class="a-input w-full" required maxlength="140"></div>
            <div><label class="mb-1 block text-sm font-semibold" for="department">Department</label><input id="department" name="department" value="{{ old('department', $vacancy->department) }}" class="a-input w-full" required maxlength="80" list="depts">
                <datalist id="depts">@foreach (['Sales', 'Warehouse', 'Logistics', 'Merchandising', 'Finance', 'Credit control', 'Administration', 'Retail'] as $d)<option value="{{ $d }}">@endforeach</datalist></div>
            <div><label class="mb-1 block text-sm font-semibold" for="location">Location</label><input id="location" name="location" value="{{ old('location', $vacancy->location) }}" class="a-input w-full" required maxlength="80"></div>
            <div><label class="mb-1 block text-sm font-semibold" for="employment_type">Type</label><input id="employment_type" name="employment_type" value="{{ old('employment_type', $vacancy->employment_type) }}" class="a-input w-full" required maxlength="30" list="etypes">
                <datalist id="etypes"><option value="Full time"><option value="Part time"><option value="Contract"><option value="Seasonal"></datalist></div>
            <div><label class="mb-1 block text-sm font-semibold" for="closes_on">Closing date <span class="font-normal text-ink-3">(optional)</span></label><input id="closes_on" name="closes_on" type="date" value="{{ old('closes_on', $vacancy->closes_on?->toDateString()) }}" class="a-input w-full"></div>
        </div>
        <div><label class="mb-1 block text-sm font-semibold" for="summary">Summary</label><textarea id="summary" name="summary" rows="3" class="a-input w-full" required maxlength="1500">{{ old('summary', $vacancy->summary) }}</textarea></div>
        <div><label class="mb-1 block text-sm font-semibold" for="duties">Duties <span class="font-normal text-ink-3">(one per line)</span></label><textarea id="duties" name="duties" rows="5" class="a-input w-full" maxlength="4000">{{ old('duties', $vacancy->duties) }}</textarea></div>
        <div><label class="mb-1 block text-sm font-semibold" for="requirements">Requirements <span class="font-normal text-ink-3">(one per line)</span></label><textarea id="requirements" name="requirements" rows="5" class="a-input w-full" maxlength="4000">{{ old('requirements', $vacancy->requirements) }}</textarea></div>
        <label class="flex min-h-11 items-center gap-2 text-sm font-semibold"><input type="checkbox" name="published" value="1" class="h-4 w-4" @checked(old('published', $vacancy->published))>Show on the careers page</label>
        <div class="flex flex-wrap gap-2"><button class="a-btn a-btn-primary">Save</button><a href="{{ route('admin.vacancies.index') }}" class="a-btn a-btn-ghost">Cancel</a></div>
    </form>
    @if ($vacancy->exists)
        <form method="POST" action="{{ route('admin.vacancies.destroy', $vacancy) }}" class="mt-4" onsubmit="return confirm('Delete this vacancy? Applications are kept.')">@csrf @method('DELETE')<button class="a-btn a-btn-ghost text-[color:var(--critical-text)]"><x-icon name="trash" class="h-4 w-4" />Delete vacancy</button></form>
    @endif
</x-layouts.admin>
