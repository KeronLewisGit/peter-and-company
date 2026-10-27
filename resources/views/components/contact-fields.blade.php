@props(['step' => 1, 'title' => 'Your details'])
{{-- Name, phone, email and how to reach you: the same on every form. --}}
<fieldset class="space-y-5">
    <legend class="fieldset-title mb-5"><span class="step">{{ $step }}</span>{{ $title }}</legend>
    <x-field name="name" label="Full name" :required="true" autocomplete="name" maxlength="120" />
    <div class="grid gap-5 sm:grid-cols-2">
        <x-field name="phone" label="Phone" type="tel" :required="true" autocomplete="tel" inputmode="tel" maxlength="30" hint="e.g. 758-452-1234" />
        <x-field name="email" label="Email" type="email" :required="true" autocomplete="email" maxlength="255" hint="We email your reference number here." />
    </div>
    <div>
        <p class="label" id="pref-label">Best way to reach you</p>
        <div class="grid gap-2 sm:grid-cols-3" role="radiogroup" aria-labelledby="pref-label">
            @foreach (['phone' => 'Phone call', 'whatsapp' => 'WhatsApp', 'email' => 'Email'] as $v => $t)
                <label class="choice"><input type="radio" name="contact_pref" value="{{ $v }}" @checked(old('contact_pref', 'phone') === $v)>{{ $t }}</label>
            @endforeach
        </div>
    </div>
    {{-- Honeypot: hidden from people, filled in by bots. --}}
    <div class="hidden" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
</fieldset>
