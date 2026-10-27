<x-layouts.site title="Photo credits" description="Licences and authors for the photographs used on this website.">
    <x-page-hero title="Photo credits" lead="The Acado St. Lucia logo is the company's own. The photographs are from Wikimedia Commons, used under the licences below." code="L-02" label="Credits" photo="marigot-bay" alt="Marigot Bay, St Lucia" label-code="CREDITS" label-line="Wikimedia Commons" />
    <section class="wrap mt-12 md:mt-16">
        <p class="measure text-ink-2">The warehouse, bottling, bar, supermarket and pharmacy photos show similar operations elsewhere, not Acado St. Lucia's own premises or products, and should be replaced with photos of the company's warehouse, fleet and team. Brand names are shown as text and belong to their owners.</p>
        <ul class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (config('photo-credits') as $key => $c)
                <li class="panel overflow-hidden">
                    <x-photo :key="$key" alt="" sizes="(min-width: 1024px) 30vw, (min-width: 640px) 45vw, 100vw" class="aspect-[3/2]" />
                    <div class="p-5 text-[0.9375rem]">
                        <p class="font-semibold text-ink">{{ $c['title'] }}</p>
                        <p class="mt-1 text-ink-2">By {{ $c['author'] }}</p>
                        <p class="mt-1 text-ink-2">
                            @if ($c['license_url'])<a class="text-link" href="{{ $c['license_url'] }}" rel="license noopener" target="_blank">{{ $c['license'] }}</a>@else{{ $c['license'] }}@endif
                            · <a class="text-link" href="{{ $c['source'] }}" rel="noopener" target="_blank">Source</a>
                        </p>
                    </div>
                </li>
            @endforeach
        </ul>
        <p class="mt-8 text-sm text-ink-3">Photos were resized and cropped for the web. CC BY-SA photos remain under their original licence.</p>
    </section>
</x-layouts.site>
