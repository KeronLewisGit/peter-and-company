@php($kept = \App\Support\UploadStash::for('principal')->names())
<x-layouts.site title="Brand owners and principals" description="Brand owners looking for distribution in St Lucia: tell Acado St. Lucia about your range, markets and plans.">
    <x-page-hero title="Distribution in St Lucia for your brand." lead="For manufacturers and brand owners outside St Lucia. Tell us about your range, where it sells today and what you need from a distributor. Our management team reviews every enquiry." code="P-01" label="Principals" photo="castries-harbour" alt="Castries harbour with cargo and cruise ships at the quay" label-code="PR · LC" label-line="Import · Warehouse · Sell in" />

    <section class="wrap mt-12 md:mt-16">
        <div class="grid gap-12 lg:grid-cols-[0.75fr_1.25fr] lg:items-start">
            <aside class="space-y-5">
                <h2 class="display-3">What a principal gets</h2>
                <dl class="facts border-t-2 border-ink">
                    <div><dt>Coverage</dt><dd>Supermarkets, minimarts, bars, hotels and pharmacies island-wide</dd></div>
                    <div><dt>Divisions</dt><dd>{{ collect(peterco('divisions'))->pluck('short')->join(', ', ' and ') }}</dd></div>
                    <div><dt>Retail</dt><dd>{{ count(peterco('retail')) }} company stores in Castries, Rodney Bay, Vieux Fort and Bois D'Orange</dd></div>
                    <div><dt>Group</dt><dd>Acado Limited, the Agostini's and Goddard Enterprises joint venture</dd></div>
                </dl>
                <p class="text-sm text-ink-3">Coverage details are to be confirmed with Acado St. Lucia before launch.</p>
            </aside>
            <div class="min-w-0">
                <x-form-errors />
                <form method="POST" action="{{ route('principals.store') }}" enctype="multipart/form-data" class="space-y-12" x-data="leadForm" @submit="submit">
                    @csrf
                    <x-contact-fields :step="1" title="About you" />
                    <fieldset class="space-y-5">
                        <legend class="fieldset-title mb-5"><span class="step">2</span>Your company and brands</legend>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-field name="business_name" label="Company" :required="true" maxlength="160" autocomplete="organization" />
                            <x-field name="job_title" label="Your job title" :required="true" maxlength="80" autocomplete="organization-title" />
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-field name="country" label="Country" :required="true" maxlength="60" autocomplete="country-name" />
                            <x-field name="company_site" label="Website" type="url" :optional="true" maxlength="200" placeholder="https://" />
                        </div>
                        <x-field name="brands" label="Brand names" :required="true" maxlength="300" />
                        <div>
                            <p class="label" id="cat-label">Categories</p>
                            <div class="grid gap-2 sm:grid-cols-2" role="group" aria-labelledby="cat-label">
                                @foreach (peterco('divisions') as $key => $d)
                                    <label class="choice"><input type="checkbox" name="categories[]" value="{{ $key }}" @checked(in_array($key, (array) old('categories', []), true))>{{ $d['short'] }}</label>
                                @endforeach
                            </div>
                            <x-error for="categories" />
                        </div>
                        <x-field name="caribbean" label="Where in the Caribbean do you sell today?" :optional="true" maxlength="500" />
                        <x-field name="message" label="Tell us about the range and what you need" type="textarea" :required="true" rows="5" maxlength="3000" />
                        <x-field name="deck" :kept="$kept['deck'] ?? null" label="Presentation or price list" type="file" :optional="true" accept=".pdf,.ppt,.pptx" hint="PDF or PowerPoint, up to 15 MB." />
                    </fieldset>
                    <x-submit label="Send enquiry" />
                </form>
            </div>
        </div>
    </section>
</x-layouts.site>
