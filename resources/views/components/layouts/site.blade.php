@props(['title' => null, 'description' => null, 'jsonLd' => [], 'mobileBar' => true, 'ogImage' => 'acado-team'])
@php
    $pageTitle = $title ? $title.' · '.peterco('short_name') : peterco('short_name').' · St Lucia\'s trade distributor since 1891';
    $metaDescription = $description ?? 'Acado St. Lucia (formerly Peter & Company Distribution), Cul De Sac, Castries: wines and spirits, beer and beverages, food, household and personal care, and pharmaceuticals for St Lucia\'s shops, bars, hotels and pharmacies.';
    $phone = peterco('phone');
    $address = peterco('address');
    $listCount = \App\Support\OrderList::count();
    $divisions = (array) peterco('divisions');
    // Acado's own menu (Home, Featured brands, About us, Our retail stores, Contact), mapped onto
    // this site's pages, with the trade pages it adds.
    $nav = [
        ['route' => 'brands.index', 'match' => 'brands.*', 'label' => 'Featured brands', 'children' => array_merge(
            collect($divisions)->map(fn (array $d, string $key) => ['href' => route('brands.show', $key), 'label' => $d['name'], 'current' => request()->is('brands/'.$key)])->values()->all(),
            [['href' => route('brands.index'), 'label' => 'All brands, by division', 'current' => request()->routeIs('brands.index')],
                ['href' => route('principals'), 'label' => 'Brand owners and principals', 'current' => request()->routeIs('principals*')]],
        )],
        ['route' => 'catalogue', 'match' => 'catalogue', 'label' => 'Catalogue'],
        ['route' => 'account.create', 'match' => 'account.*', 'label' => 'Trade accounts'],
        ['route' => 'about', 'match' => 'about', 'label' => 'About us', 'children' => [
            ['href' => route('about'), 'label' => 'Who we are', 'current' => request()->routeIs('about')],
            ['href' => route('about').'#retail', 'label' => 'Our retail stores', 'current' => false],
            ['href' => route('careers.index'), 'label' => 'Careers', 'current' => request()->routeIs('careers.*')],
        ]],
        // Careers also stands on its own, so job seekers find it without opening "About us".
        ['route' => 'careers.index', 'match' => 'careers.*', 'label' => 'Careers'],
        ['route' => 'contact', 'match' => 'contact', 'label' => 'Contact'],
    ];
    $socialIcons = ['Facebook' => 'facebook', 'LinkedIn' => 'linkedin', 'Instagram' => 'instagram'];
    $organization = [
        '@context' => 'https://schema.org',
        '@type' => 'WholesaleStore',
        'name' => peterco('name'),
        'alternateName' => ['Peter & Company Distribution', 'Peter & Company Limited', 'PCD'],
        'url' => route('home'),
        'telephone' => $phone ? '+'.us_digits($phone) : null,
        'foundingDate' => (string) peterco('founded'),
        'logo' => asset('img/brand/acado-st-lucia.png'),
        'image' => asset('img/acado-team-1366.webp'),
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
    @php($og = (array) (config('peterco.photos.'.$ogImage)['widths'] ?? [1920]))
    <meta property="og:image" content="{{ asset('img/'.$ogImage.'-'.end($og).'.webp') }}">
    <meta property="og:locale" content="en_LC">
    <meta name="twitter:card" content="summary_large_image">
    @foreach (array_merge([array_filter($organization)], $jsonLd) as $ld)
        <script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endforeach
</head>
<body class="min-h-screen {{ $mobileBar ? 'pb-[5.25rem] lg:pb-0' : '' }}" x-data="{ menu: false }" @keydown.escape.window="menu = false">
    <div class="scroll-progress" aria-hidden="true"></div>
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded focus:bg-surface focus:px-4 focus:py-3 focus:shadow-md">Skip to content</a>

    @if (peterco('preview'))
        <div class="bg-yellow text-sm text-navy">
            <p class="wrap flex items-start gap-2 py-2.5"><x-icon name="alert" class="mt-0.5 h-4 w-4 flex-none" /><span><strong class="font-bold text-navy">Concept preview prepared for Acado St. Lucia Limited.</strong> This is not the official Acado St. Lucia website. Requests sent here do not reach the company.</span></p>
        </div>
    @endif

    {{-- Acado's orange top bar: the former name, the head-office line and the social links --}}
    <div class="topbar text-[0.875rem]">
        <div class="wrap flex min-h-11 items-center justify-between gap-3">
            <p class="truncate font-medium">Formerly Peter &amp; Company Distribution<span class="hidden md:inline"> · {{ $address['area'] }}, {{ $address['city'] }} · Trade customers</span></p>
            <div class="flex flex-none items-center">
                @if ($phone)<a href="{{ tel_href($phone) }}" class="inline-flex min-h-11 items-center gap-1.5 px-2 font-semibold"><x-icon name="phone" class="h-3.5 w-3.5" />{{ format_phone($phone) }}</a>@endif
                <ul class="hidden items-center sm:flex" aria-label="Acado St. Lucia on social media">
                    @foreach ((array) peterco('social') as $network => $url)
                        <li><a class="inline-flex h-11 w-10 items-center justify-center rounded-full hover:bg-black/10" href="{{ $url }}" rel="noopener" target="_blank"><x-icon :name="$socialIcons[$network] ?? 'external'" class="h-4 w-4" /><span class="sr-only">{{ $network }} (opens in a new tab)</span></a></li>
                    @endforeach
                </ul>
                <a href="{{ route('careers.index') }}" class="hidden min-h-11 items-center gap-1.5 px-2 font-semibold sm:inline-flex" data-topbar-careers @if(request()->routeIs('careers.*')) aria-current="page" @endif><x-icon name="briefcase" class="h-3.5 w-3.5" />Careers</a>
                <a href="{{ route('login') }}" class="hidden min-h-11 items-center pl-2 font-medium md:inline-flex">Staff</a>
            </div>
        </div>
    </div>

    <header class="site-head sticky top-0 z-40 border-b border-line bg-surface/95 shadow-sm backdrop-blur">
        <div class="wrap flex h-[4.75rem] items-center gap-1.5 min-[400px]:gap-2 lg:gap-1 xl:gap-2">
            <a href="{{ route('home') }}" class="mr-auto inline-flex min-h-11 flex-none items-center rounded" aria-label="Acado St. Lucia, home"><x-logo /></a>

            <nav class="hidden items-center lg:flex" aria-label="Main">
                <a href="{{ route('home') }}" class="nav-link hidden xl:inline-flex" @if(request()->routeIs('home')) aria-current="page" @endif>Home</a>
                @foreach ($nav as $item)
                    @php($current = request()->routeIs($item['match']))
                    @if (! empty($item['children']))
                        <div class="nav-item">
                            <a href="{{ route($item['route']) }}" class="nav-link" @if($current) aria-current="page" @endif>{{ $item['label'] }}<x-icon name="chevron-down" class="h-3.5 w-3.5" /></a>
                            <div class="nav-drop">
                                @foreach ($item['children'] as $child)
                                    <a href="{{ $child['href'] }}" @if($child['current']) aria-current="page" @endif>{{ $child['label'] }}</a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ route($item['route']) }}" class="nav-link" @if($current) aria-current="page" @endif>{{ $item['label'] }}</a>
                    @endif
                @endforeach
            </nav>

            <a href="{{ route('catalogue') }}#cat-q" class="inline-flex h-11 w-11 flex-none items-center justify-center rounded-full text-primary hover:bg-surface-2" aria-label="Search the catalogue"><x-icon name="search" class="h-5 w-5" /></a>
            <button type="button" onclick="toggleTheme(event)" class="hidden h-11 w-11 flex-none items-center justify-center rounded-full text-ink-2 hover:bg-surface-2 hover:text-ink sm:inline-flex" aria-label="Switch light or dark theme">
                <x-icon name="moon" class="h-5 w-5 dark-hide" /><x-icon name="sun" class="hidden h-5 w-5 dark-show" />
            </button>
            <a href="{{ route('order.show') }}" data-order-target class="btn btn-primary btn-sm ml-1 hidden flex-none whitespace-nowrap lg:inline-flex" @if(request()->routeIs('order.*')) aria-current="page" @endif><x-icon name="list" class="h-4 w-4" /><span class="max-xl:sr-only">Order list</span><span class="num rounded-full bg-black/20 px-2 text-sm" data-order-count>{{ $listCount }}</span><span class="sr-only xl:hidden"> lines</span></a>
            <button type="button" class="menu-toggle inline-flex h-11 w-11 flex-none items-center justify-center rounded-full text-ink ring-1 ring-line-strong lg:hidden" @click="menu = !menu" :aria-expanded="menu.toString()" aria-controls="mobile-menu" aria-label="Menu">
                <x-icon name="menu" x-show="!menu" /><x-icon name="x" x-show="menu" x-cloak />
            </button>
        </div>
        <div id="mobile-menu" class="max-h-[calc(100dvh-7.5rem)] overflow-y-auto border-t border-line bg-surface lg:hidden" x-show="menu" x-cloak x-transition.opacity>
            <nav class="mnav wrap flex flex-col pb-6 pt-2" aria-label="Mobile">
                <a href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>Home</a>
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}" @if(request()->routeIs($item['match'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                    @if (! empty($item['children']))
                        <div class="sub">
                            @foreach ($item['children'] as $child)
                                @continue($child['href'] === route($item['route']))
                                <a href="{{ $child['href'] }}" @if($child['current']) aria-current="page" @endif>{{ $child['label'] }}</a>
                            @endforeach
                        </div>
                    @endif
                @endforeach
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <a href="{{ route('order.show') }}" class="btn btn-primary">Order list (<span data-order-count>{{ $listCount }}</span>)</a>
                    @if ($phone)<a href="{{ tel_href($phone) }}" class="btn btn-outline"><x-icon name="phone" class="h-4 w-4" />{{ format_phone($phone) }}</a>@endif
                </div>
                <button type="button" onclick="toggleTheme(event)" class="mt-3 flex min-h-11 items-center gap-2 rounded px-1 text-left font-semibold text-ink-2"><x-icon name="moon" class="h-5 w-5" />Switch light or dark theme</button>
            </nav>
        </div>
    </header>

    @if (session('list_status'))
        <div class="border-b border-line bg-primary-soft" role="status">
            <p class="wrap flex flex-wrap items-center gap-x-4 gap-y-1 py-3 font-semibold text-ink"><span class="inline-flex items-center gap-2" data-flash><x-icon name="check-circle" class="h-5 w-5 text-link" />{{ session('list_status') }}</span><a href="{{ route('order.show') }}" class="text-link underline underline-offset-2">View order list ({{ $listCount }})</a></p>
        </div>
    @endif

    <main id="main">
        {{ $slot }}
    </main>

    {{-- Acado's navy band, on every page as on acadostlucia.com --}}
    <section class="band-navy spotlight on-dark mt-24" aria-labelledby="partner-title" data-spotlight>
        <div class="wrap flex flex-col gap-6 py-12 md:flex-row md:items-center md:justify-between md:py-14">
            <div>
                <h2 id="partner-title" class="text-2xl font-medium leading-snug text-white md:text-[1.75rem]">Looking for an outstanding distribution partner?</h2>
                <p class="mt-2 max-w-[60ch] text-white/85">Shops, bars, hotels and pharmacies can open a trade account online. Brand owners can tell us about their range.</p>
            </div>
            <div class="flex flex-none flex-wrap gap-3">
                <a href="{{ route('contact') }}" class="btn btn-yellow-outline">Contact us</a>
                <a href="{{ route('account.create') }}" class="btn btn-yellow">Open a trade account<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
            </div>
        </div>
    </section>

    <footer class="site-foot on-orange bg-[var(--footer)] text-white">
        <div class="wrap grid gap-12 py-14 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1.1fr]">
            <div>
                <x-logo tone="light" class="[&_img]:!h-14" />
                <p class="font-display mt-5 text-lg font-medium text-white">{{ peterco('tagline') }}</p>
                <p class="mt-3 max-w-xs text-white">Formerly Peter &amp; Company Distribution. Distributing to St Lucia's shops, bars, hotels and pharmacies from Cul De Sac since 1891.</p>
                @if ($phone)<a href="{{ tel_href($phone) }}" class="font-display mt-5 inline-flex min-h-12 items-center gap-2.5 text-xl font-semibold text-white hover:underline"><x-icon name="phone" class="h-5 w-5" />Tel: {{ format_phone($phone) }}</a>@endif
                @if (peterco('email'))<p class="mt-1"><a class="inline-flex min-h-11 items-center hover:underline" href="mailto:{{ peterco('email') }}">{{ peterco('email') }}</a></p>@endif
            </div>
            <div>
                <h2 class="kicker text-white">Trade</h2>
                <ul class="mt-4 space-y-0.5">
                    <li><a class="inline-flex min-h-11 items-center hover:underline" href="{{ route('catalogue') }}">Trade catalogue</a></li>
                    <li><a class="inline-flex min-h-11 items-center hover:underline" href="{{ route('order.show') }}">Request an order</a></li>
                    <li><a class="inline-flex min-h-11 items-center hover:underline" href="{{ route('account.create') }}">Open a trade account</a></li>
                    <li><a class="inline-flex min-h-11 items-center hover:underline" href="{{ route('brands.show', 'pharmaceutical') }}">Pharmacies and clinics</a></li>
                    <li><a class="inline-flex min-h-11 items-center hover:underline" href="{{ route('principals') }}">Brand owners and principals</a></li>
                </ul>
            </div>
            <div>
                <h2 class="kicker text-white">Company</h2>
                <ul class="mt-4 space-y-0.5">
                    <li><a class="inline-flex min-h-11 items-center hover:underline" href="{{ route('brands.index') }}">Featured brands</a></li>
                    <li><a class="inline-flex min-h-11 items-center hover:underline" href="{{ route('about') }}">Who we are</a></li>
                    <li><a class="inline-flex min-h-11 items-center hover:underline" href="{{ route('about') }}#retail">Our retail stores</a></li>
                    <li><a class="inline-flex min-h-11 items-center hover:underline" href="{{ route('careers.index') }}">Careers</a></li>
                    <li><a class="inline-flex min-h-11 items-center hover:underline" href="{{ route('contact') }}">Contact</a></li>
                </ul>
            </div>
            <div>
                <h2 class="kicker text-white">Head office</h2>
                <address class="mt-5 not-italic leading-relaxed">
                    {{ peterco('name') }}<br>{{ $address['area'] }}, {{ $address['po_box'] }}<br>{{ $address['city'] }}, {{ $address['country'] }}
                </address>
                <a class="mt-1 inline-flex min-h-11 items-center gap-1.5 font-semibold hover:underline" href="{{ peterco('map_url') }}" rel="noopener" target="_blank">Get directions<x-icon name="external" class="h-3.5 w-3.5" /><span class="sr-only">(opens Google Maps)</span></a>
            </div>
        </div>
        <div class="border-t border-white/25 bg-[var(--footer-2)]">
            <div class="wrap flex flex-col items-center gap-3 py-6 text-center text-sm md:flex-row md:justify-between md:text-left">
                <p>© {{ date('Y') }} {{ peterco('name') }}. All rights reserved. Brand names and logos belong to their owners.</p>
                <ul class="flex items-center gap-1" aria-label="Acado St. Lucia on social media">
                    @foreach ((array) peterco('social') as $network => $url)
                        <li><a class="inline-flex h-11 w-11 items-center justify-center rounded-full hover:bg-white/15" href="{{ $url }}" rel="noopener" target="_blank"><x-icon :name="$socialIcons[$network] ?? 'external'" class="h-[18px] w-[18px]" /><span class="sr-only">{{ $network }} (opens in a new tab)</span></a></li>
                    @endforeach
                </ul>
                <p class="flex flex-wrap justify-center gap-x-5">
                    <a class="inline-flex min-h-11 items-center hover:underline" href="{{ route('privacy') }}">Privacy</a>
                    <a class="inline-flex min-h-11 items-center hover:underline" href="{{ route('credits') }}">Photo credits</a>
                    <a class="inline-flex min-h-11 items-center hover:underline" href="{{ route('sitemap.page') }}">Site map</a>
                    <a class="inline-flex min-h-11 items-center hover:underline" href="{{ route('login') }}">Staff sign in</a>
                </p>
            </div>
        </div>
    </footer>

    @if ($mobileBar)
        {{-- Thumb-reach actions on phones: call, the catalogue, the order list --}}
        <nav aria-label="Quick actions" class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface/95 px-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-3 shadow-[0_-8px_24px_-16px_rgb(0_20_51/0.4)] backdrop-blur lg:hidden">
            <div class="flex gap-2">
                @if ($phone)<a href="{{ tel_href($phone) }}" class="btn btn-outline flex-1 px-2 text-[0.875rem]"><x-icon name="phone" class="h-4 w-4" />Call</a>@endif
                <a href="{{ route('catalogue') }}" class="btn btn-outline flex-1 px-2 text-[0.875rem]">Catalogue</a>
                <a href="{{ route('order.show') }}" data-order-target class="btn btn-primary flex-[1.3] whitespace-nowrap px-2 text-[0.875rem]">Order list · <span data-order-count>{{ $listCount }}</span></a>
            </div>
        </nav>
    @endif
    {{-- Toasts for "Add to order" (JavaScript only; without it the page reloads with the message above) --}}
    <div class="toasts" data-toasts role="status" aria-live="polite"></div>
</body>
</html>
