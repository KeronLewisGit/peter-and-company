@php($kept = \App\Support\UploadStash::for('application')->names())
<x-layouts.site :title="$vacancy ? 'Apply: '.$vacancy->title : 'Send your CV'" description="Apply for a job at Acado St. Lucia. Attach your CV; only HR can open it.">
    <x-page-hero :title="$vacancy ? 'Apply: '.$vacancy->title : 'Send your CV'" :lead="$vacancy ? $vacancy->summary : 'A general application for warehouse, delivery, merchandising, sales or office work. HR keeps it on file for twelve months.'" code="H-03" label="Application" photo="warehouse-racking" alt="" label-code="CV" label-line="HR only">
        <nav class="mt-6 text-sm text-ink-3" aria-label="Breadcrumb"><a href="{{ route('careers.index') }}" class="inline-flex min-h-11 items-center text-link underline underline-offset-2">Careers</a> / Apply</nav>
    </x-page-hero>
    <section class="wrap mt-12 md:mt-16">
        <div class="max-w-3xl">
            <x-form-errors />
            <form method="POST" action="{{ route('careers.store') }}" enctype="multipart/form-data" class="space-y-12" x-data="leadForm" @submit="submit">
                @csrf
                <x-contact-fields :step="1" title="About you" />
                <fieldset class="space-y-5">
                    <legend class="fieldset-title mb-5"><span class="step">2</span>The job</legend>
                    <x-select name="vacancy" label="Vacancy" :options="$vacancies" :value="$vacancy?->slug" placeholder="General application" />
                    <x-field name="role_interest" label="If general: the kind of work you want" maxlength="120" hint="e.g. Warehouse, driving, merchandising, sales, accounts" />
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-select name="district" label="Where you live" :required="true" :options="peterco('districts')" />
                        <x-select name="available" label="When you can start" :required="true" :options="peterco('availability')" />
                    </div>
                    <div>
                        <p class="label" id="dl-label">Driving licence</p>
                        <div class="grid gap-2 sm:grid-cols-3" role="radiogroup" aria-labelledby="dl-label">
                            @foreach (['none' => 'None', 'car' => 'Car', 'commercial' => 'Commercial vehicles'] as $v => $t)
                                <label class="choice"><input type="radio" name="driving_licence" value="{{ $v }}" @checked(old('driving_licence') === $v) required>{{ $t }}</label>
                            @endforeach
                        </div>
                        <x-error for="driving_licence" />
                    </div>
                    <x-field name="experience" label="Your experience" type="textarea" :optional="true" rows="4" maxlength="2000" />
                </fieldset>
                <fieldset class="space-y-5">
                    <legend class="fieldset-title mb-5"><span class="step">3</span>Your CV</legend>
                    <x-field name="cv" :kept="$kept['cv'] ?? null" label="CV" type="file" :required="true" accept=".pdf,.doc,.docx" hint="PDF or Word, up to 8 MB. Only HR can open it." />
                    <x-field name="letter" :kept="$kept['letter'] ?? null" label="Cover letter" type="file" :optional="true" accept=".pdf,.doc,.docx" />
                    <label class="choice"><input type="checkbox" name="consent" value="1" @checked(old('consent')) required><span>I agree that Acado St. Lucia may keep my application on file for twelve months.</span></label>
                    <x-error for="consent" />
                </fieldset>
                <x-submit label="Send application" />
            </form>
        </div>
    </section>
</x-layouts.site>
