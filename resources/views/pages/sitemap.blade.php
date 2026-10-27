<x-layouts.site title="Site map" description="Every page on the Acado St. Lucia website.">
    <x-page-hero title="Site map" lead="Every page on this website." code="L-03" label="All pages" photo="castries-harbour" alt="" label-code="MAP" label-line="All pages" />
    <section class="wrap mt-12 grid gap-10 md:mt-16 md:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            'Trade' => ['Trade catalogue' => route('catalogue'), 'Order list' => route('order.show'), 'Open a trade account' => route('account.create'), 'Brand owners and principals' => route('principals')],
            'Brands' => ['All brands' => route('brands.index')] + collect(peterco('divisions'))->mapWithKeys(fn ($d, $k) => [$d['name'] => route('brands.show', $k)])->all(),
            'Company' => ['About us' => route('about'), 'Our retail stores' => route('about').'#retail', 'Careers' => route('careers.index'), 'Send your CV' => route('careers.apply'), 'Contact' => route('contact')],
            'Legal' => ['Privacy notice' => route('privacy'), 'Photo credits' => route('credits'), 'Staff sign in' => route('login')],
        ] as $group => $links)
            <div>
                <h2 class="text-lg font-bold">{{ $group }}</h2>
                <ul class="mt-3 space-y-1">@foreach ($links as $t => $u)<li><a class="text-link inline-flex min-h-11 items-center" href="{{ $u }}">{{ $t }}</a></li>@endforeach</ul>
            </div>
        @endforeach
        @if ($vacancies->isNotEmpty())
            <div class="md:col-span-2">
                <h2 class="text-lg font-bold">Open vacancies</h2>
                <ul class="mt-3 space-y-1">@foreach ($vacancies as $v)<li><a class="text-link inline-flex min-h-11 items-center" href="{{ route('careers.show', $v->slug) }}">{{ $v->title }}</a></li>@endforeach</ul>
            </div>
        @endif
    </section>
</x-layouts.site>
