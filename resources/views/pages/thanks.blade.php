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
    <x-page-hero title="Thank you. We have it." :lead="$next" code="OK" :label="$type->label()" photo="pitons" alt="" :label-code="$reference" label-line="Received" />
    <section class="wrap mt-12 md:mt-16">
        <div class="pallet-label max-w-md" role="group" aria-label="Your reference">
            <div class="row"><span>Your reference</span><span>{{ $type->label() }}</span></div>
            <p class="big !text-3xl sm:!text-4xl">{{ $reference }}</p>
            <div class="barcode" aria-hidden="true"></div>
        </div>
        <p class="measure mt-6 text-ink-2">We've emailed it to you too. Quote it if you call, and we'll find your request straight away.</p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ tel_href(peterco('phone')) }}" class="btn btn-outline"><x-icon name="phone" class="h-4 w-4" />{{ format_phone(peterco('phone')) }}</a>
            <a href="{{ route('catalogue') }}" class="btn btn-outline">Back to the catalogue</a>
            <a href="{{ route('home') }}" class="btn btn-primary">Home page</a>
        </div>
    </section>
</x-layouts.site>
