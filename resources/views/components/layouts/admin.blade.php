@props(['title', 'actions' => null])
@php
    /** @var \App\Models\User $me */
    $me = auth()->user();
    $role = $me->role;
    $overdue = \App\Models\Lead::visibleTo($me)->overdue()->count();
    $newLeads = \App\Models\Lead::visibleTo($me)->where('status', 'new')->count();
    $nav = [['Overview', 'admin.dashboard', 'admin.dashboard', 'grid', null, true]];
    $nav[] = ['Requests inbox', 'admin.leads.index', 'admin.leads.*', 'inbox', $newLeads, true];
    $nav[] = ['Credit board', 'admin.credit.index', 'admin.credit.*', 'shield', null, $me->hasRole(\App\Enums\Role::Admin, \App\Enums\Role::Credit)];
    $nav[] = ['Trade accounts', 'admin.accounts.index', 'admin.accounts.*', 'building', null, $me->hasRole(\App\Enums\Role::Admin, \App\Enums\Role::Sales, \App\Enums\Role::Credit)];
    $nav[] = ['Catalogue', 'admin.products.index', 'admin.products.*', 'crate', null, $me->hasRole(\App\Enums\Role::Admin, \App\Enums\Role::Sales)];
    $nav[] = ['Vacancies', 'admin.vacancies.index', 'admin.vacancies.*', 'briefcase', null, $me->hasRole(\App\Enums\Role::Admin, \App\Enums\Role::Hr)];
    $nav[] = ['Staff', 'admin.users.index', 'admin.users.*', 'users', null, $me->isAdmin()];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head')
    <title>{{ $title }} · Acado St. Lucia back office</title>
    <meta name="robots" content="noindex">
</head>
<body class="admin min-h-screen bg-bg text-ink" x-data="adminShell" @keydown.escape.window="nav = false">
    <a href="#content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-surface focus:px-4 focus:py-3">Skip to content</a>

    <div class="fixed inset-0 z-30 bg-black/40 lg:hidden" style="display:none" x-show="nav" x-transition.opacity @click="nav = false"></div>
    <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-crate-950 transition-transform lg:translate-x-0" :class="nav && 'translate-x-0'" aria-label="Dashboard">
        <a href="{{ route('admin.dashboard') }}" class="flex h-16 items-center px-5"><x-logo tone="light" :small="true" /></a>
        <nav class="admin-nav flex-1 space-y-1 px-3 pt-4">
            @foreach ($nav as [$label, $route, $match, $icon, $badge, $show])
                @continue(! $show)
                <a href="{{ route($route) }}" @if(request()->routeIs($match)) aria-current="page" @endif>
                    <x-icon :name="$icon" class="h-[18px] w-[18px]" />{{ $label }}
                    @if ($badge)<span class="num ml-auto rounded bg-sky px-2 py-0.5 text-xs font-bold text-[#0d1b24]" aria-label="{{ $badge }} new">{{ $badge }}</span>@endif
                </a>
            @endforeach
        </nav>
        <div class="admin-nav space-y-1 border-t border-white/10 px-3 py-4">
            <a href="{{ route('home') }}" target="_blank" rel="noopener"><x-icon name="external" class="h-[18px] w-[18px]" />View website</a>
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="flex min-h-11 w-full items-center gap-3 rounded px-[0.85rem] text-left font-medium text-white/80 hover:bg-white/5 hover:text-white"><x-icon name="log-out" class="h-[18px] w-[18px]" />Sign out</button>
            </form>
            <p class="truncate px-3 pt-2 text-xs text-white/60">{{ $me->name }} · {{ $role->label() }}</p>
        </div>
    </aside>

    <div class="lg:pl-64">
        <header class="sticky top-0 z-20 border-b border-line bg-bg/90 backdrop-blur">
            <div class="flex h-16 items-center gap-3 px-4 sm:px-6">
                <button type="button" class="a-btn a-btn-ghost w-11 px-0 lg:hidden" @click="nav = true" aria-label="Open navigation"><x-icon name="menu" /></button>
                <form action="{{ route('admin.leads.index') }}" method="GET" role="search" class="relative max-w-md flex-1">
                    <label for="admin-search" class="sr-only">Search requests</label>
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-3" />
                    <input id="admin-search" data-search name="q" value="{{ input_string(request('q')) }}" class="a-input w-full pl-9 pr-10" placeholder="Name, business, phone or reference" autocomplete="off">
                    <span class="kbd absolute right-2.5 top-1/2 hidden -translate-y-1/2 sm:inline" aria-hidden="true">/</span>
                </form>
                @if ($overdue)
                    <a href="{{ route('admin.leads.index', ['overdue' => 1]) }}" class="a-btn hidden bg-[color:var(--critical)] text-white sm:inline-flex"><x-icon name="clock" class="h-4 w-4" /><span class="num">{{ $overdue }}</span> past response target</a>
                @endif
                <div class="ml-auto flex items-center gap-1">
                    <button type="button" class="a-btn w-11 px-0 text-ink-2 hover:bg-surface-2" @click="toggleDensity()" :aria-pressed="(density === 'compact').toString()" aria-label="Compact rows" title="Compact rows"><x-icon name="density" /></button>
                    <button type="button" class="a-btn w-11 px-0 text-ink-2 hover:bg-surface-2" onclick="toggleTheme()" aria-label="Switch light or dark theme"><x-icon name="moon" class="dark-hide h-5 w-5" /><x-icon name="sun" class="dark-show h-5 w-5" /></button>
                </div>
            </div>
        </header>

        <main id="content" class="px-4 py-6 sm:px-6 lg:py-8">
            <div class="mb-5 flex min-h-11 flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl font-semibold tracking-tight">{{ $title }}</h1>
                @if ($actions)<div class="flex flex-wrap gap-2">{{ $actions }}</div>@endif
            </div>
            @if (session('status'))
                <div class="mb-5 flex items-center gap-2.5 rounded-md bg-surface px-5 py-4 text-sm font-medium shadow-sm" role="status"><x-icon name="check-circle" class="h-5 w-5 text-[color:var(--good)]" />{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-5 rounded-md bg-surface px-5 py-4 text-sm shadow-sm" role="alert">
                    <p class="flex items-center gap-2 font-semibold"><x-icon name="alert" class="h-5 w-5 text-[color:var(--critical)]" />Please fix the following</p>
                    <ul class="mt-1 list-disc pl-11">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif
            {{ $slot }}
        </main>
    </div>
</body>
</html>
