@php
    $districts = (array) peterco('districts');
    $docHint = 'PDF, JPG or PNG, up to 10 MB. A clear phone photo is fine.';
    $kept = \App\Support\UploadStash::for('account')->names();
@endphp
<x-layouts.site title="Open a trade account" description="Apply for a cash-on-delivery or credit trade account with Acado St. Lucia. Upload your trade licence, VAT certificate and ID securely.">
    <x-page-hero title="Open a trade account" lead="For shops, bars, hotels, restaurants, pharmacies and clinics in St Lucia. Apply for cash on delivery or credit terms; credit control reviews your documents and calls you." label="Trade account" photo="castries-market" alt="Shoppers and stalls at the Castries market" />

    <section class="wrap mt-12 md:mt-16">
        <div class="grid gap-12 lg:grid-cols-[1.25fr_0.75fr] lg:items-start">
            <div class="min-w-0">
                <x-form-errors />
                <form method="POST" action="{{ route('account.store') }}" enctype="multipart/form-data" class="space-y-12"
                      x-data="tradeForm({ tradeType: @js(old('trade_type', '')), vat: @js(old('vat_registered', '')), terms: @js(old('terms', 'cod')) })" @submit="submit">
                    @csrf
                    <x-contact-fields :step="1" title="Who is applying" />
                    <x-field name="position" label="Your position in the business" :required="true" maxlength="80" hint="e.g. Owner, Manager, Director, Purchasing officer" />

                    <fieldset class="space-y-5">
                        <legend class="fieldset-title mb-5"><span class="step">2</span>Your business</legend>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-field name="business_name" label="Registered business name" :required="true" maxlength="160" autocomplete="organization" />
                            <x-field name="trading_as" label="Trading as" :optional="true" maxlength="160" hint="If the shop name is different." />
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-select name="trade_type" label="Type of business" :required="true" :options="\App\Enums\TradeType::options()" x-model="tradeType" />
                            <x-select name="years_trading" label="Time trading" :required="true" :options="peterco('years_trading')" />
                        </div>
                        <div class="grid gap-5 sm:grid-cols-2">
                            <x-field name="trade_licence_no" label="Trade licence number" :required="true" maxlength="40" />
                            <x-field name="registration_no" label="Company registration number" :optional="true" maxlength="40" />
                        </div>
                        <div>
                            <p class="label" id="vat-label">VAT-registered?</p>
                            <div class="grid gap-2 sm:grid-cols-2" role="radiogroup" aria-labelledby="vat-label">
                                <label class="choice"><input type="radio" name="vat_registered" value="yes" x-model="vat" @checked(old('vat_registered') === 'yes') required>Yes, VAT-registered</label>
                                <label class="choice"><input type="radio" name="vat_registered" value="no" x-model="vat" @checked(old('vat_registered') === 'no')>Not VAT-registered</label>
                            </div>
                            <x-error for="vat_registered" />
                        </div>
                        <div x-show="vat !== 'no'"><x-field name="vat_no" label="VAT registration number" maxlength="30" hint="Required if you are VAT-registered." /></div>
                        <div class="grid gap-5 sm:grid-cols-[1fr_2fr_0.6fr]">
                            <x-select name="district" label="District" :required="true" :options="$districts" />
                            <x-field name="address" label="Business address" :required="true" maxlength="500" autocomplete="street-address" />
                            <x-field name="outlets" label="Outlets" type="number" :optional="true" min="1" max="200" inputmode="numeric" />
                        </div>
                        <div class="carton space-y-5 p-5" x-show="medical || tradeType === ''">
                            <p class="flex items-center gap-2 font-bold"><x-icon name="pill" class="h-5 w-5 text-link" />Pharmacies and clinics</p>
                            <p class="-mt-3 text-[0.9375rem] text-ink-2">Required for pharmacies and clinics. Pharmaceutical products are supplied to licensed buyers only.</p>
                            <div class="grid gap-5 sm:grid-cols-2">
                                <x-field name="pharmacy_licence_no" label="Pharmacy or clinic licence number" maxlength="40" />
                                <x-field name="pharmacist" label="Responsible pharmacist or medical director" maxlength="120" />
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="space-y-5">
                        <legend class="fieldset-title mb-5"><span class="step">3</span>Payment terms</legend>
                        <div class="grid gap-2 sm:grid-cols-2" role="radiogroup" aria-label="Payment terms">
                            @foreach (\App\Enums\PaymentTerms::cases() as $t)
                                <label class="choice"><input type="radio" name="terms" value="{{ $t->value }}" x-model="terms" @checked(old('terms', 'cod') === $t->value)><span>{{ $t->label() }}</span></label>
                            @endforeach
                        </div>
                        <div class="space-y-5" x-show="terms === 'credit'">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <x-field name="credit_limit" label="Credit limit you are asking for (EC$)" type="number" :optional="true" min="0" inputmode="numeric" hint="Credit control sets the final limit." />
                                <x-field name="bank_name" label="Your bank" :optional="true" maxlength="80" hint="The bank name only. Never send account numbers." />
                            </div>
                            <p class="label !mb-0">Trade references</p>
                            <p class="hint !mt-0">Suppliers who give you credit today. One is required for credit terms.</p>
                            <div class="grid gap-5 sm:grid-cols-2">
                                <x-field name="ref1_name" label="Reference 1: business" maxlength="120" />
                                <x-field name="ref1_phone" label="Reference 1: phone" type="tel" maxlength="30" />
                                <x-field name="ref2_name" label="Reference 2: business" :optional="true" maxlength="120" />
                                <x-field name="ref2_phone" label="Reference 2: phone" type="tel" :optional="true" maxlength="30" />
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="space-y-5">
                        <legend class="fieldset-title mb-5"><span class="step">4</span>Documents</legend>
                        <p class="-mt-2 text-ink-2">Stored privately, outside the public website. Only credit control can open them, and every time a document is opened it is logged.</p>
                        <x-field name="trade_licence" :kept="$kept['trade_licence'] ?? null" label="Trade licence" type="file" :required="true" accept=".pdf,.jpg,.jpeg,.png,.heic" :hint="$docHint" />
                        <div x-show="vat !== 'no'"><x-field name="vat_certificate" :kept="$kept['vat_certificate'] ?? null" label="VAT certificate" type="file" accept=".pdf,.jpg,.jpeg,.png,.heic" hint="Required if you are VAT-registered." /></div>
                        <x-field name="id_document" :kept="$kept['id_document'] ?? null" label="Photo ID of the owner or director" type="file" :required="true" accept=".pdf,.jpg,.jpeg,.png,.heic" hint="National ID, passport or driving permit." />
                        <div x-show="medical || tradeType === ''"><x-field name="pharmacy_licence" :kept="$kept['pharmacy_licence'] ?? null" label="Pharmacy or clinic licence" type="file" accept=".pdf,.jpg,.jpeg,.png,.heic" hint="Pharmacies and clinics only." /></div>
                        <div x-show="terms === 'credit'"><x-field name="bank_reference" :kept="$kept['bank_reference'] ?? null" label="Bank reference letter" type="file" :optional="true" accept=".pdf,.jpg,.jpeg,.png,.heic" /></div>
                    </fieldset>

                    <fieldset class="space-y-5">
                        <legend class="fieldset-title mb-5"><span class="step">5</span>Confirm and send</legend>
                        <x-field name="message" label="Anything else credit control should know" type="textarea" :optional="true" rows="3" maxlength="1500" />
                        <label class="choice"><input type="checkbox" name="consent" value="1" @checked(old('consent')) required><span>I agree that Acado St. Lucia may check these documents and contact my trade references and bank.</span></label>
                        <x-error for="consent" />
                        <label class="choice"><input type="checkbox" name="declaration" value="1" @checked(old('declaration')) required><span>The details I have given are true, and I am authorised to apply for this business.</span></label>
                        <x-error for="declaration" />
                        <x-submit label="Send application" />
                    </fieldset>
                </form>
            </div>
            <aside class="space-y-5 lg:sticky lg:top-28">
                <div class="carton p-6">
                    <h2 class="text-lg font-bold">What you will need</h2>
                    <ul class="mt-3 space-y-2 text-[0.9375rem] text-ink-2">
                        @foreach (['Your trade licence', 'Your VAT certificate, if VAT-registered', 'Photo ID of the owner or a director', 'Pharmacies and clinics: your licence', 'For credit: one or two trade references'] as $i)
                            <li class="flex gap-2"><x-icon name="check" class="mt-1 h-4 w-4 flex-none text-link" />{{ $i }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="carton p-6">
                    <h2 class="text-lg font-bold">After you apply</h2>
                    <p class="mt-2 text-[0.9375rem] text-ink-2">Credit control reviews the application, may ask for a missing document, and emails your account number once it is approved. You can send order requests in the meantime; they are matched to your account by phone.</p>
                </div>
            </aside>
        </div>
    </section>
</x-layouts.site>
