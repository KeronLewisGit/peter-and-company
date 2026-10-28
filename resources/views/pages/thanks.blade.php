@php
    $next = match ($type) {
        \App\Enums\LeadType::Order => 'The trade desk will call or message you to confirm prices, availability and your delivery day. Nothing ships until you confirm.',
        \App\Enums\LeadType::Account => 'Credit control will review your documents and call you. If anything is missing, they will tell you what to send.',
        \App\Enums\LeadType::Principal => 'Our management team will review your enquiry and reply by email.',
        \App\Enums\LeadType::Application => 'HR will review your CV. If you are shortlisted, they will call you to arrange an interview.',
        default => 'The right team will get back to you during office hours.',
    };
@endphp
<x-layouts.site title="Thank you" :mobile-bar="false">
    <x-page-hero title="Thank you. We have it." :lead="$next" :label="$type->label()" photo="pitons" alt="" />
    <section class="wrap mt-12 md:mt-16">
        <div class="carton relative max-w-md overflow-hidden p-6" role="group" aria-label="Your reference">
            <span class="absolute inset-x-0 top-0 h-1 bg-[linear-gradient(90deg,var(--orange),var(--yellow))]" aria-hidden="true"></span>
            <p class="kicker text-ink-3">Your reference · {{ $type->label() }}</p>
            <p class="font-display num mt-2 text-3xl font-semibold text-title sm:text-4xl">{{ $reference }}</p>
        </div>
        <p class="measure mt-6 text-ink-2">We've emailed it to you too. Quote it if you call, and we'll find your request straight away.</p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ tel_href(peterco('phone')) }}" class="btn btn-outline"><x-icon name="phone" class="h-4 w-4" />{{ format_phone(peterco('phone')) }}</a>
            <a href="{{ route('catalogue') }}" class="btn btn-outline">Back to the catalogue</a>
            <a href="{{ route('home') }}" class="btn btn-primary">Home page</a>
        </div>
    </section>
</x-layouts.site>
