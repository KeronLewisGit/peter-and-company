<x-layouts.site :title="$vacancy->title" :description="$vacancy->summary">
    <x-page-hero :title="$vacancy->title" :lead="$vacancy->summary" :label="$vacancy->department" photo="acado-careers" position="70% 30%" alt="">
        <nav class="mt-6 text-sm text-ink-3" aria-label="Breadcrumb"><a href="{{ route('careers.index') }}" class="inline-flex min-h-11 items-center text-link underline underline-offset-2">Careers</a> / {{ $vacancy->title }}</nav>
    </x-page-hero>
    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-[1.25fr_0.75fr] lg:items-start">
        <div class="min-w-0 measure">
            @if ($vacancy->is_demo)<p class="mb-6"><span class="sample-badge">Sample vacancy for the website preview</span></p>@endif
            <h2 class="display-3">What you will do</h2>
            <ul class="mt-4 space-y-2">@foreach ($vacancy->dutyList() as $d)<li class="flex gap-3"><x-icon name="check" class="mt-1 h-4 w-4 flex-none text-link" />{{ $d }}</li>@endforeach</ul>
            <h2 class="display-3 mt-10">What you need</h2>
            <ul class="mt-4 space-y-2">@foreach ($vacancy->requirementList() as $r)<li class="flex gap-3"><x-icon name="check" class="mt-1 h-4 w-4 flex-none text-link" />{{ $r }}</li>@endforeach</ul>
        </div>
        <aside class="carton p-6 lg:sticky lg:top-28">
            <dl class="facts">
                <div><dt>Department</dt><dd>{{ $vacancy->department }}</dd></div>
                <div><dt>Location</dt><dd>{{ $vacancy->location }}</dd></div>
                <div><dt>Type</dt><dd>{{ $vacancy->employment_type }}</dd></div>
                @if ($vacancy->closes_on)<div><dt>Closes</dt><dd>{{ $vacancy->closes_on->format('j F Y') }}</dd></div>@endif
            </dl>
            <a href="{{ route('careers.apply', ['vacancy' => $vacancy->slug]) }}" class="btn btn-primary mt-6 w-full">Apply for this job<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </aside>
    </section>
</x-layouts.site>
