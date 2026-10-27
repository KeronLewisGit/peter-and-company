<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Staff accounts and roles. Accounts are switched off rather than deleted, so lead history keeps its names. */
class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.users', ['users' => User::orderByDesc('active')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::enum(Role::class)],
            'password' => ['required', 'string', 'min:10'],
        ]);
        $user = User::create(['email' => strtolower($data['email'])] + $data + ['active' => true]);

        return back()->with('status', "{$user->name} can now sign in.");
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::enum(Role::class)],
            'active' => ['nullable', 'boolean'],
        ]);
        $active = $request->boolean('active');
        if ($user->is($request->user()) && (! $active || $data['role'] !== Role::Admin->value)) {
            return back()->withErrors(['role' => 'You can\'t remove your own admin access.']);
        }
        $user->update(['role' => $data['role'], 'active' => $active]);

        return back()->with('status', "{$user->name} saved.");
    }
}
