@php($phone = peterco('phone'))
<x-layouts.site title="Contact" description="Contact Acado St. Lucia at Cul De Sac, Castries: head office phone, directions and an enquiry form.">
    <x-page-hero title="Contact us" lead="Head office and warehouse at Cul De Sac, just south of Castries. Call, or send a message and the right team will reply." code="K-01" label="Contact" photo="cul-de-sac-terminal" alt="Cul de Sac Bay, south of Castries" position="center" label-code="CUL DE SAC" label-line="Castries · LC" />

    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
        <aside class="space-y-5">
            <div class="carton p-6 pt-7">
                <h2 class="kicker text-ink-3">Head office</h2>
                @if ($phone)<a href="{{ tel_href($phone) }}" class="font-display mt-3 inline-flex min-h-12 items-center gap-2 text-2xl font-extrabold"><x-icon name="phone" class="h-5 w-5 text-link" />{{ format_phone($phone) }}</a>@endif
                @if (peterco('email'))<p><a class="text-link inline-flex min-h-11 items-center" href="mailto:{{ peterco('email') }}">{{ peterco('email') }}</a></p>@endif
                <address class="mt-3 not-italic text-ink-2">{{ peterco('name') }}<br>{{ peterco('address.area') }}, {{ peterco('address.po_box') }}<br>{{ peterco('address.city') }}, {{ peterco('address.country') }}</address>
                <a class="btn btn-outline btn-sm mt-5" href="{{ peterco('map_url') }}" rel="noopener" target="_blank">Get directions<x-icon name="external" class="h-4 w-4" /><span class="sr-only">(opens Google Maps)</span></a>
            </div>
            <div class="carton p-6 pt-7">
                <h2 class="text-lg font-bold">Faster routes</h2>
                <ul class="mt-3 space-y-1">
                    <li><a class="text-link inline-flex min-h-11 items-center font-semibold" href="{{ route('order.show') }}">Send a trade order request</a></li>
                    <li><a class="text-link inline-flex min-h-11 items-center font-semibold" href="{{ route('account.create') }}">Apply for a trade account</a></li>
                    <li><a class="text-link inline-flex min-h-11 items-center font-semibold" href="{{ route('principals') }}">Distribution for your brand</a></li>
                    <li><a class="text-link inline-flex min-h-11 items-center font-semibold" href="{{ route('careers.index') }}">Jobs and CVs</a></li>
                </ul>
            </div>
            <p class="text-sm text-ink-3">Office hours to be confirmed with the company. Shoppers: see <a class="text-link" href="{{ route('about') }}#retail">our retail stores</a>.</p>
        </aside>
        <div class="min-w-0">
            <x-form-errors />
            <form method="POST" action="{{ route('contact.store') }}" class="space-y-10" x-data="leadForm" @submit="submit">
                @csrf
                <x-contact-fields :step="1" />
                <fieldset class="space-y-5">
                    <legend class="fieldset-title mb-5"><span class="step">2</span>Your message</legend>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-field name="business_name" label="Business" :optional="true" maxlength="160" autocomplete="organization" />
                        <x-select name="topic" label="About" :required="true" :options="peterco('enquiry_topics')" />
                    </div>
                    <x-field name="message" label="Message" type="textarea" :required="true" rows="5" maxlength="3000" />
                </fieldset>
                <x-submit label="Send message" />
            </form>
        </div>
    </section>
</x-layouts.site>
