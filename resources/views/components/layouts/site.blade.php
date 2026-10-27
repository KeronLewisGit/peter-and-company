@props(['title' => null, 'description' => null, 'jsonLd' => [], 'mobileBar' => true, 'ogImage' => 'castries-city-port'])
@php
    $pageTitle = $title ? $title.' · '.peterco('short_name') : peterco('short_name').' · St Lucia\'s trade distributor since 1891';
    $metaDescription = $description ?? 'Acado St. Lucia (formerly Peter & Company Distribution), Cul De Sac, Castries: wines and spirits, beer and beverages, food, household and personal care, and pharmaceuticals for St Lucia\'s shops, bars, hotels and pharmacies.';
    $phone = peterco('phone');
    $address = peterco('address');
    $listCount = \App\Support\OrderList::count();
    $nav = [
        ['route' => 'brands.index', 'match' => 'brands.*', 'label' => 'Brands'],
        ['route' => 'catalogue', 'match' => 'catalogue', 'label' => 'Catalogue'],
        ['route' => 'account.create', 'match' => 'account.*', 'label' => 'Trade accounts'],
        ['route' => 'principals', 'match' => 'principals*', 'label' => 'Principals'],
        ['route' => 'careers.index', 'match' => 'careers.*', 'label' => 'Careers'],
        ['route' => 'about', 'match' => 'about', 'label' => 'About'],
        ['route' => 'contact', 'match' => 'contact', 'label' => 'Contact'],
    ];
    $organization = [
        '@context' => 'https://schema.org',
        '@type' => 'WholesaleStore',
        'name' => peterco('name'),
        'alternateName' => ['Peter & Company Distribution', 'Peter & Company Limited', 'PCD'],
        'url' => route('home'),
        'telephone' => $phone ? '+'.us_digits($phone) : null,
        'foundingDate' => (string) peterco('founded'),
        'logo' => asset('img/brand/acado-st-lucia.png'),
        'image' => asset('img/castries-city-port-1920.webp'),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $address['area'].', '.$address['po_box'],
            'addressLocality' => $address['city'],
            'addressCountry' => $address['country_code'],
        ],
        'areaServed' => 'Saint Lucia',
        'sameAs' => array_values((array) peterco('social')),
    ];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head')
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    @if (peterco('preview'))
        <meta name="robots" content="noindex, nofollow">
    @endif
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ peterco('name') }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('img/'.$ogImage.'-1920.webp') }}">
    <meta property="og:locale" content="en_LC">
    <meta name="twitter:card" content="summary_large_image">
    @foreach (array_merge([array_filter($organization)], $jsonLd) as $ld)
        <script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endforeach
</head>
<body class="min-h-screen {{ $mobileBar ? 'pb-[5.25rem] lg:pb-0' : '' }}" x-data="{ menu: false }" @keydown.escape.window="menu = false">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-surface focus:px-4 focus:py-3 focus:shadow-md">Skip to content</a>

    @if (peterco('preview'))
        <div class="bg-sky text-sm text-[#0d1b24]">
            <p class="wrap flex items-start gap-2 py-2.5"><x-icon name="alert" class="mt-0.5 h-4 w-4 flex-none" /><span><strong class="font-bold">Concept preview prepared for Acado St. Lucia Limited.</strong> This is not the official Acado St. Lucia website. Requests sent here do not reach the company.</span></p>
        </div>
    @endif

    {{-- Utility strip: who we are, where, and the head-office line --}}
    <div class="bg-crate-900 text-[0.875rem] text-white/80 on-dark">
        <div class="wrap flex min-h-10 items-center justify-between gap-4">
            <p class="truncate">Formerly Peter &amp; Company Distribution<span class="hidden md:inline"> · {{ $address['area'] }}, {{ $address['city'] }} · Trade customers only</span></p>
            <div class="flex flex-none items-center gap-1">
                @if ($phone)<a href="{{ tel_href($phone) }}" class="inline-flex min-h-10 items-center gap-1.5 px-1 font-semibold text-white hover:underline"><x-icon name="phone" class="h-3.5 w-3.5" />{{ format_phone($phone) }}</a>@endif
                <a href="{{ route('login') }}" class="hidden min-h-10 items-center px-2 hover:text-white sm:inline-flex">Staff</a>
            </div>
        </div>
    </div>

    <header class="sticky top-0 z-40 border-b-[1.5px] border-ink bg-bg/95 backdrop-blur">
        <div class="wrap flex h-[4.5rem] items-center gap-2 min-[400px]:gap-3">
            <a href="{{ route('home') }}" class="mr-auto inline-flex min-h-11 flex-none items-center rounded" aria-label="Acado St. Lucia, home"><x-logo /></a>

            <nav class="hidden items-center lg:flex" aria-label="Main">
                @foreach ($nav as $item)
                    @php($current = request()->routeIs($item['match']))
                    <a href="{{ route($item['route']) }}" @class(['relative whitespace-nowrap px-2 py-2.5 text-[0.9375rem] font-semibold transition-colors xl:px-3', 'text-ink' => $current, 'text-ink-2 hover:text-ink' => ! $current]) @if($current) aria-current="page" @endif>
                        {{ $item['label'] }}
                        @if ($current)<span class="absolute inset-x-2 -bottom-[0.95rem] h-[4px] bg-stripe xl:inset-x-3" aria-hidden="true"></span>@endif
                    </a>
                @endforeach
            </nav>

            <button type="button" onclick="toggleTheme()" class="hidden h-11 w-11 flex-none items-center justify-center rounded text-ink-2 hover:bg-surface-2 hover:text-ink sm:inline-flex" aria-label="Switch light or dark theme">
                <x-icon name="moon" class="h-5 w-5 dark-hide" /><x-icon name="sun" class="hidden h-5 w-5 dark-show" />
            </button>
            <a href="{{ route('order.show') }}" class="btn btn-primary btn-sm hidden flex-none whitespace-nowrap lg:inline-flex" @if(request()->routeIs('order.*')) aria-current="page" @endif><x-icon name="list" class="h-4 w-4" />Order list<span class="num rounded bg-white/20 px-1.5 text-sm">{{ $listCount }}</span></a>
            <button type="button" class="inline-flex h-11 w-11 flex-none items-center justify-center rounded ring-[1.5px] ring-ink lg:hidden" @click="menu = !menu" :aria-expanded="menu.toString()" aria-controls="mobile-menu" aria-label="Menu">
                <x-icon name="menu" x-show="!menu" /><x-icon name="x" x-show="menu" x-cloak />
            </button>
        </div>
        <div id="mobile-menu" class="border-t border-line bg-bg lg:hidden" x-show="menu" x-cloak x-transition.opacity>
            <nav class="wrap flex flex-col py-3" aria-label="Mobile">
                <a href="{{ route('home') }}" class="rounded px-1 py-3 text-xl font-bold">Home</a>
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}" class="rounded px-1 py-3 text-xl font-bold" @if(request()->routeIs($item['match'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                @endforeach
                <div class="mt-3 grid gap-3 border-t border-line pt-5 sm:grid-cols-2">
                    <a href="{{ route('order.show') }}" class="btn btn-primary">Order list ({{ $listCount }})</a>
                    @if ($phone)<a href="{{ tel_href($phone) }}" class="btn btn-outline"><x-icon name="phone" class="h-4 w-4" />{{ format_phone($phone) }}</a>@endif
                </div>
                <button type="button" onclick="toggleTheme()" class="mt-3 flex min-h-11 items-center gap-2 rounded px-1 text-left font-semibold text-ink-2"><x-icon name="moon" class="h-5 w-5" />Switch light or dark theme</button>
            </nav>
        </div>
    </header>

    @if (session('list_status'))
        <div class="border-b border-line bg-sky-soft" role="status">
            <p class="wrap flex flex-wrap items-center gap-x-4 gap-y-1 py-3 font-semibold"><span class="inline-flex items-center gap-2"><x-icon name="check-circle" class="h-5 w-5 text-link" />{{ session('list_status') }}</span><a href="{{ route('order.show') }}" class="text-link underline underline-offset-2">View order list ({{ $listCount }})</a></p>
        </div>
    @endif

    <main id="main">
        {{ $slot }}
    </main>

    <footer class="site-foot relative mt-24 bg-crate-900 text-white/75">
        <div class="flutes-top absolute inset-x-0 -top-[10px]" aria-hidden="true"></div>
        <div class="wrap grid gap-12 py-16 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1.1fr]">
            <div>
                <x-logo tone="light" />
                <p class="mt-5 max-w-xs text-white/75">Formerly Peter &amp; Company Distribution. Distributing to St Lucia's shops, bars, hotels and pharmacies from Cul De Sac, with roots back to 1891.</p>
                @if ($phone)<a href="{{ tel_href($phone) }}" class="font-display mt-6 inline-flex min-h-12 items-center gap-2.5 text-2xl font-extrabold text-white hover:text-sky"><x-icon name="phone" class="h-5 w-5" />{{ format_phone($phone) }}</a>@endif
                @if (peterco('email'))<p class="mt-1"><a class="inline-flex min-h-11 items-center hover:text-white" href="mailto:{{ peterco('email') }}">{{ peterco('email') }}</a></p>@endif
            </div>
            <div>
                <h2 class="kicker text-white/60">Trade</h2>
                <ul class="mt-4 space-y-1">
                    <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('catalogue') }}">Trade catalogue</a></li>
                    <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('order.show') }}">Request an order</a></li>
                    <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('account.create') }}">Open a trade account</a></li>
                    <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('brands.show', 'pharmaceutical') }}">Pharmacies and clinics</a></li>
                    <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('principals') }}">Brand owners and principals</a></li>
                </ul>
            </div>
            <div>
                <h2 class="kicker text-white/60">Company</h2>
                <ul class="mt-4 space-y-1">
                    <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('brands.index') }}">Our brands</a></li>
                    <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('about') }}">About us</a></li>
                    <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('careers.index') }}">Careers</a></li>
                    <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('about') }}#retail">Our retail stores</a></li>
                    <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('contact') }}">Contact</a></li>
                </ul>
            </div>
            <div>
                <h2 class="kicker text-white/60">Head office</h2>
                <address class="mt-5 not-italic leading-relaxed">
                    {{ peterco('name') }}<br>{{ $address['area'] }}, {{ $address['po_box'] }}<br>{{ $address['city'] }}, {{ $address['country'] }}
                </address>
                <a class="mt-1 inline-flex min-h-11 items-center gap-1.5 hover:text-white" href="{{ peterco('map_url') }}" rel="noopener" target="_blank">Get directions<x-icon name="external" class="h-3.5 w-3.5" /><span class="sr-only">(opens Google Maps)</span></a>
                <ul class="mt-3 flex flex-wrap gap-x-4">
                    @foreach ((array) peterco('social') as $network => $url)
                        <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ $url }}" rel="noopener" target="_blank">{{ $network }}<span class="sr-only"> (opens in a new tab)</span></a></li>
                    @endforeach
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="wrap flex flex-col gap-3 py-6 text-sm text-white/65 md:flex-row md:items-center md:justify-between">
                <p>© {{ date('Y') }} {{ peterco('name') }}. {{ peterco('tagline') }} Brand names belong to their owners.</p>
                <p class="flex flex-wrap gap-x-5">
                    <a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('privacy') }}">Privacy</a>
                    <a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('credits') }}">Photo credits</a>
                    <a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('sitemap.page') }}">Site map</a>
                    <a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('login') }}">Staff sign in</a>
                </p>
            </div>
        </div>
    </footer>

    @if ($mobileBar)
        {{-- Thumb-reach actions on phones: call, the catalogue, the order list --}}
        <nav aria-label="Quick actions" class="fixed inset-x-0 bottom-0 z-30 border-t-[1.5px] border-ink bg-bg/95 px-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-3 backdrop-blur lg:hidden">
            <div class="flex gap-2">
                @if ($phone)<a href="{{ tel_href($phone) }}" class="btn btn-outline flex-1 px-2 text-[0.9375rem]"><x-icon name="phone" class="h-4 w-4" />Call</a>@endif
                <a href="{{ route('catalogue') }}" class="btn btn-outline flex-1 px-2 text-[0.9375rem]">Catalogue</a>
                <a href="{{ route('order.show') }}" class="btn btn-primary flex-[1.3] whitespace-nowrap px-2 text-[0.9375rem]">Order list · {{ $listCount }}</a>
            </div>
        </nav>
    @endif
</body>
</html>
