@props(['title', 'lead' => null, 'label' => 'Acado St. Lucia', 'photo' => null, 'position' => 'center', 'alt' => '', 'square' => false])
{{--
    The one hero every inner page uses, so titles start at the same height and left edge
    site-wide. As on acadostlucia.com, the page title is set in Acado orange on white; here it
    sits under a small eyebrow label with the lead beside a rounded photo (under the text on
    phones), and the band ends in Acado's orange-to-yellow rule. Extra content (breadcrumbs,
    actions, filters) goes in the slot.
--}}
<section {{ $attributes->merge(['class' => 'relative isolate overflow-hidden bg-surface']) }} data-hero="page" data-inview>
    <div class="pointer-events-none absolute -right-40 -top-48 -z-10 h-[30rem] w-[30rem] rounded-full bg-[radial-gradient(closest-side,rgb(255_189_11/0.16),rgb(226_104_14/0.08)_55%,transparent)]" aria-hidden="true"></div>
    <div @class(['wrap grid gap-8 pb-10 pt-9 md:pb-14 md:pt-14 lg:items-center lg:gap-14', 'lg:grid-cols-[1.2fr_0.8fr]' => $photo && ! $square, 'lg:grid-cols-[1.35fr_0.65fr]' => $photo && $square])>
        <div class="min-w-0">
            <x-rack-tag :label="$label" />
            <h1 class="display-2 mt-4 max-w-3xl text-title">{{ $title }}</h1>
            @if ($lead)<p class="lead mt-4 text-ink-2 md:mt-5">{{ $lead }}</p>@endif
            {{ $slot }}
        </div>
        @if ($photo)
            <div @class(['relative', 'mx-auto w-full max-w-sm lg:max-w-none' => $square])>
                <div class="absolute -bottom-3 -right-3 h-2/3 w-2/3 rounded-[var(--radius)] bg-[linear-gradient(135deg,var(--orange),var(--yellow))] opacity-90" aria-hidden="true"></div>
                <div @class(['hero-photo framed photo relative', 'aspect-square' => $square, 'aspect-[16/9] lg:aspect-[4/3]' => ! $square])>
                    <x-photo :key="$photo" :alt="$alt" :eager="true" sizes="(min-width: 1024px) 40vw, 100vw" class="h-full w-full" :position="$position" />
                </div>
            </div>
        @endif
    </div>
    <div class="gradient-rule" aria-hidden="true"></div>
</section>
