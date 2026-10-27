<x-layouts.admin title="Staff">
    <div class="grid gap-6 xl:grid-cols-[1.5fr_1fr]">
        <div class="a-card relative overflow-x-auto">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Role</th><th>Sees</th><th>Active</th><th><span class="sr-only">Save</span></th></tr></thead>
                <tbody>
                    @foreach ($users as $u)
                        <tr>
                            <td><p class="font-semibold">{{ $u->name }}</p><p class="text-sm text-ink-3">{{ $u->email }}</p></td>
                            <td colspan="4" class="!p-0">
                                <form method="POST" action="{{ route('admin.users.update', $u) }}" class="flex flex-wrap items-center gap-3 px-3.5 py-2">
                                    @csrf @method('PATCH')
                                    <label class="sr-only" for="role-{{ $u->id }}">Role for {{ $u->name }}</label>
                                    <select id="role-{{ $u->id }}" name="role" class="a-input">@foreach (\App\Enums\Role::cases() as $r)<option value="{{ $r->value }}" @selected($u->role === $r)>{{ $r->label() }}</option>@endforeach</select>
                                    <span class="min-w-40 flex-1 text-sm text-ink-3">{{ collect($u->role->leadTypes())->map->label()->join(', ') }}</span>
                                    <label class="flex min-h-11 items-center gap-2 text-sm"><input type="checkbox" name="active" value="1" class="h-4 w-4" @checked($u->active)>Active</label>
                                    <button class="a-btn a-btn-ghost">Save</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <form method="POST" action="{{ route('admin.users.store') }}" class="a-card h-fit space-y-4 p-5">
            @csrf
            <h2 class="font-semibold">Add a staff member</h2>
            <div><label for="n" class="mb-1 block text-sm font-semibold">Name</label><input id="n" name="name" value="{{ old('name') }}" class="a-input w-full" required></div>
            <div><label for="e" class="mb-1 block text-sm font-semibold">Email</label><input id="e" name="email" type="email" value="{{ old('email') }}" class="a-input w-full" required></div>
            <div><label for="r" class="mb-1 block text-sm font-semibold">Role</label><select id="r" name="role" class="a-input w-full">@foreach (\App\Enums\Role::cases() as $r)<option value="{{ $r->value }}" @selected(old('role') === $r->value)>{{ $r->label() }}</option>@endforeach</select></div>
            <div><label for="p" class="mb-1 block text-sm font-semibold">Temporary password</label><input id="p" name="password" type="password" minlength="10" class="a-input w-full" required autocomplete="new-password"><p class="mt-1 text-xs text-ink-3">At least 10 characters. Share it in person.</p></div>
            <button class="a-btn a-btn-primary w-full">Add staff member</button>
            <p class="text-xs text-ink-3">Credit documents (trade licence, VAT certificate, ID, pharmacy licence) open only for Credit control, including for administrators. CVs open for HR and administrators.</p>
        </form>
    </div>
</x-layouts.admin>
