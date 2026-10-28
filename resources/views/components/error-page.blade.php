@props(['code'])
@php
    $copy = [
        '404' => ['That page has moved', 'The link may be from an old website. Everything is still here: the brands, the trade catalogue, trade accounts and careers.'],
        '403' => ['You can\'t open that', 'Your account doesn\'t have access to this page. Ask an administrator if you need it.'],
        '419' => ['Your session timed out', 'For your security the form expired. Go back, refresh the page and send it again.'],
        '429' => ['Too many tries', 'Please wait a minute and try again, or call us.'],
        '500' => ['Something went wrong on our side', 'Please try again in a minute. If you were sending a form, call us and we will take it by phone.'],
        '503' => ['Back shortly', 'We are updating the website. Please try again in a few minutes.'],
    ][$code] ?? ['Something went wrong', 'Please try again.'];
@endphp
<x-layouts.site :title="$copy[0]" :mobile-bar="false">
    <x-page-hero :title="$copy[0]" :lead="$copy[1]" label="Error" photo="marigot-bay" alt="" />
    <section class="wrap mt-12 flex flex-wrap gap-3 md:mt-16">
        <a href="{{ route('home') }}" class="btn btn-primary">Go to the home page<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        @if ($code === '404')
            <a href="{{ route('catalogue') }}" class="btn btn-outline">Trade catalogue</a>
        @endif
        <a href="{{ tel_href(peterco('phone')) }}" class="btn btn-outline"><x-icon name="phone" class="h-4 w-4" />{{ format_phone(peterco('phone')) }}</a>
    </section>
</x-layouts.site>
