<?php

namespace App\Http\Controllers;

use App\Enums\LeadType;
use App\Enums\PaymentTerms;
use App\Enums\TradeType;
use App\Models\Vacancy;
use App\Support\LeadIntake;
use App\Support\StashedUpload;
use App\Support\UploadStash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\View\View;

/**
 * The trade account (credit) application, the principal enquiry, the job application and
 * the contact form. Each validates, hands over to LeadIntake (reference + emails) and lands
 * on /thanks. Order requests have their own controller.
 */
class LeadFormController extends Controller
{
    /** Scans and phone photos of documents. HEIC is what iPhones save by default. */
    private const string DOC_MIMES = 'mimes:pdf,jpg,jpeg,png,heic,heif';

    private const string CV_MIMES = 'mimes:pdf,doc,docx';

    // ---------------------------------------------------------------- trade account

    public function account(): View
    {
        return view('pages.forms.account');
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        if (LeadIntake::isBot($request)) {
            return to_route('thanks');
        }
        $medical = fn () => TradeType::tryFrom(input_string($request->input('trade_type')))?->isMedical() === true;
        $doc = ['file', self::DOC_MIMES, 'max:10240'];
        $stash = UploadStash::for('account');
        $stash->keep($request, array_fill_keys(['trade_licence', 'vat_certificate', 'id_document', 'pharmacy_licence', 'bank_reference'], $doc));
        $validator = validator($request->all(), $stash->relax($this->contactRules() + [
            'position' => ['required', 'string', 'max:80'],
            'business_name' => ['required', 'string', 'max:160'],
            'trading_as' => ['nullable', 'string', 'max:160'],
            'trade_type' => ['required', Rule::enum(TradeType::class)],
            'registration_no' => ['nullable', 'string', 'max:40'],
            'trade_licence_no' => ['required', 'string', 'max:40'],
            'vat_registered' => ['required', Rule::in(['yes', 'no'])],
            'vat_no' => ['nullable', 'required_if:vat_registered,yes', 'string', 'max:30'],
            'years_trading' => ['required', Rule::in(array_keys((array) peterco('years_trading')))],
            'outlets' => ['nullable', 'integer', 'min:1', 'max:200'],
            'address' => ['required', 'string', 'max:500'],
            'district' => ['required', Rule::in(array_keys((array) peterco('districts')))],
            'pharmacy_licence_no' => [Rule::requiredIf($medical), 'nullable', 'string', 'max:40'],
            'pharmacist' => [Rule::requiredIf($medical), 'nullable', 'string', 'max:120'],
            'terms' => ['required', Rule::enum(PaymentTerms::class)],
            'credit_limit' => ['nullable', 'integer', 'min:0', 'max:5000000'],
            'bank_name' => ['nullable', 'string', 'max:80'],
            'ref1_name' => ['nullable', 'required_if:terms,credit', 'string', 'max:120'],
            'ref1_phone' => ['nullable', 'required_if:terms,credit', 'string', 'max:30'],
            'ref2_name' => ['nullable', 'string', 'max:120'],
            'ref2_phone' => ['nullable', 'string', 'max:30'],
            'trade_licence' => ['required', 'file', self::DOC_MIMES, 'max:10240'],
            'vat_certificate' => ['nullable', 'required_if:vat_registered,yes', 'file', self::DOC_MIMES, 'max:10240'],
            'id_document' => ['required', 'file', self::DOC_MIMES, 'max:10240'],
            'pharmacy_licence' => [Rule::requiredIf($medical), 'nullable', 'file', self::DOC_MIMES, 'max:10240'],
            'bank_reference' => ['nullable', 'file', self::DOC_MIMES, 'max:10240'],
            'consent' => ['accepted'],
            'declaration' => ['accepted'],
            'message' => ['nullable', 'string', 'max:1500'],
        ], $request), [
            'consent.accepted' => 'We need your consent to check your references and documents.',
            'declaration.accepted' => 'Please confirm the details are correct.',
            'vat_no.required_if' => 'Enter your VAT registration number.',
            'vat_certificate.required_if' => 'Upload your VAT certificate, or choose "Not VAT-registered".',
            'pharmacy_licence_no.required' => 'Pharmacies and clinics must give their licence number.',
            'pharmacist.required' => 'Tell us the responsible pharmacist or medical director.',
            'pharmacy_licence.required' => 'Upload your pharmacy or clinic licence.',
            'ref1_name.required_if' => 'A credit account needs at least one trade reference.',
            'ref1_phone.required_if' => 'Give the trade reference\'s phone number.',
        ], [
            'trade_type' => 'type of business', 'trade_licence_no' => 'trade licence number', 'years_trading' => 'time trading',
            'trade_licence' => 'trade licence', 'id_document' => 'ID', 'credit_limit' => 'credit limit',
        ]);
        $this->refuseBankNumbers($validator, $request);
        $data = $validator->validate();

        $type = TradeType::from($data['trade_type']);
        LeadIntake::create(LeadType::Account, $this->contactFields($data) + [
            'business_name' => trim($data['business_name']),
            'consented_at' => now(),
            'message' => $data['message'] ?? null,
            'details' => [
                'position' => $data['position'],
                'trading_as' => $data['trading_as'] ?? null,
                'trade_type' => $type->value,
                'trade_type_label' => $type->label(),
                'registration_no' => $data['registration_no'] ?? null,
                'trade_licence_no' => $data['trade_licence_no'],
                'vat_registered' => $data['vat_registered'] === 'yes',
                'vat_no' => $data['vat_no'] ?? null,
                'years_trading' => $data['years_trading'],
                'outlets' => isset($data['outlets']) ? (int) $data['outlets'] : null,
                'address' => trim($data['address']),
                'district' => $data['district'],
                'pharmacy_licence_no' => $type->isMedical() ? $data['pharmacy_licence_no'] : null,
                'pharmacist' => $type->isMedical() ? $data['pharmacist'] : null,
                'terms' => $data['terms'],
                'credit_limit' => isset($data['credit_limit']) ? (int) $data['credit_limit'] : null,
                'bank_name' => $data['bank_name'] ?? null,
                'references' => array_values(array_filter([
                    ['name' => $data['ref1_name'] ?? null, 'phone' => $data['ref1_phone'] ?? null],
                    ['name' => $data['ref2_name'] ?? null, 'phone' => $data['ref2_phone'] ?? null],
                ], fn (array $r) => filled($r['name']))),
            ],
        ], [
            'trade_licence' => $this->upload($request, $stash, 'trade_licence'),
            'vat_certificate' => $this->upload($request, $stash, 'vat_certificate'),
            'id' => $this->upload($request, $stash, 'id_document'),
            'pharmacy_licence' => $type->isMedical() ? $this->upload($request, $stash, 'pharmacy_licence') : [],
            'bank_reference' => $this->upload($request, $stash, 'bank_reference'),
        ], $request);
        $stash->clear();

        return to_route('thanks');
    }

    // ---------------------------------------------------------------- principals

    public function principal(): View
    {
        return view('pages.principals');
    }

    public function storePrincipal(Request $request): RedirectResponse
    {
        if (LeadIntake::isBot($request)) {
            return to_route('thanks');
        }
        $stash = UploadStash::for('principal');
        $stash->keep($request, ['deck' => ['file', 'mimes:pdf,ppt,pptx', 'max:15360']]);
        $data = $request->validate($stash->relax($this->contactRules() + [
            'business_name' => ['required', 'string', 'max:160'],
            'job_title' => ['required', 'string', 'max:80'],
            'country' => ['required', 'string', 'max:60'],
            // Not "website": that name is the honeypot.
            'company_site' => ['nullable', 'url:http,https', 'max:200'],
            'brands' => ['required', 'string', 'max:300'],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => [Rule::in(array_keys((array) peterco('divisions')))],
            'caribbean' => ['nullable', 'string', 'max:500'],
            'deck' => ['nullable', 'file', 'mimes:pdf,ppt,pptx', 'max:15360'],
            'message' => ['required', 'string', 'max:3000'],
        ], $request), [
            'categories.required' => 'Choose at least one category.',
            'categories.*.in' => 'Choose categories from the list.',
            'company_site.url' => 'Enter the full web address, starting with https://',
        ], ['business_name' => 'company', 'company_site' => 'website', 'brands' => 'brand names', 'deck' => 'presentation']);

        LeadIntake::create(LeadType::Principal, $this->contactFields($data) + [
            'business_name' => trim($data['business_name']),
            'message' => $data['message'],
            'details' => [
                'job_title' => $data['job_title'],
                'country' => $data['country'],
                'company_site' => $data['company_site'] ?? null,
                'brands' => $data['brands'],
                'categories' => array_values($data['categories']),
                'caribbean' => $data['caribbean'] ?? null,
            ],
        ], ['deck' => $this->upload($request, $stash, 'deck')], $request);
        $stash->clear();

        return to_route('thanks');
    }

    // ---------------------------------------------------------------- careers

    public function application(Request $request): View
    {
        $vacancy = Vacancy::open()->where('slug', input_string($request->query('vacancy')))->first();

        return view('pages.forms.application', [
            'vacancy' => $vacancy,
            'vacancies' => Vacancy::open()->orderBy('title')->pluck('title', 'slug'),
        ]);
    }

    public function storeApplication(Request $request): RedirectResponse
    {
        if (LeadIntake::isBot($request)) {
            return to_route('thanks');
        }
        $stash = UploadStash::for('application');
        $stash->keep($request, array_fill_keys(['cv', 'letter'], ['file', self::CV_MIMES, 'max:8192']));
        $data = $request->validate($stash->relax($this->contactRules() + [
            'vacancy' => ['nullable', 'string', Rule::exists('vacancies', 'slug')->where('published', true)],
            'role_interest' => ['nullable', 'required_without:vacancy', 'string', 'max:120'],
            'district' => ['required', Rule::in(array_keys((array) peterco('districts')))],
            'available' => ['required', Rule::in(array_keys((array) peterco('availability')))],
            'driving_licence' => ['required', Rule::in(['none', 'car', 'commercial'])],
            'experience' => ['nullable', 'string', 'max:2000'],
            'cv' => ['required', 'file', self::CV_MIMES, 'max:8192'],
            'letter' => ['nullable', 'file', self::CV_MIMES, 'max:8192'],
            'consent' => ['accepted'],
        ], $request), [
            'role_interest.required_without' => 'Tell us what kind of work you are looking for.',
            'cv.required' => 'Attach your CV (PDF or Word).',
            'cv.mimes' => 'Your CV must be a PDF or Word document.',
            'consent.accepted' => 'We need your consent to keep your application on file.',
        ], ['cv' => 'CV', 'letter' => 'cover letter', 'available' => 'availability']);

        $vacancy = isset($data['vacancy']) ? Vacancy::open()->where('slug', $data['vacancy'])->first() : null;
        if (isset($data['vacancy']) && ! $vacancy) {
            return back()->withInput()->withErrors(['vacancy' => 'That vacancy has closed. You can still send a general application.']);
        }
        LeadIntake::create(LeadType::Application, $this->contactFields($data) + [
            'vacancy_id' => $vacancy?->id,
            'consented_at' => now(),
            'message' => $data['experience'] ?? null,
            'details' => [
                'role_interest' => $vacancy ? null : ($data['role_interest'] ?? null),
                'district' => $data['district'],
                'available' => $data['available'],
                'driving_licence' => $data['driving_licence'],
            ],
        ], [
            'cv' => $this->upload($request, $stash, 'cv'),
            'letter' => $this->upload($request, $stash, 'letter'),
        ], $request);
        $stash->clear();

        return to_route('thanks');
    }

    // ---------------------------------------------------------------- contact

    public function storeEnquiry(Request $request): RedirectResponse
    {
        if (LeadIntake::isBot($request)) {
            return to_route('thanks');
        }
        $data = $request->validate($this->contactRules() + [
            'business_name' => ['nullable', 'string', 'max:160'],
            'topic' => ['required', Rule::in(array_keys((array) peterco('enquiry_topics')))],
            'message' => ['required', 'string', 'max:3000'],
        ]);
        LeadIntake::create(LeadType::Enquiry, $this->contactFields($data) + [
            'business_name' => isset($data['business_name']) ? trim($data['business_name']) : null,
            'message' => $data['message'],
            'details' => ['topic' => $data['topic']],
        ], [], $request);

        return to_route('thanks');
    }

    public function thanks(Request $request): View|RedirectResponse
    {
        $last = $request->session()->get('last_lead');

        return is_array($last) ? view('pages.thanks', ['reference' => $last['reference'], 'type' => LeadType::tryFrom($last['type']) ?? LeadType::Enquiry]) : to_route('home');
    }

    // ---------------------------------------------------------------- shared

    /**
     * The file for a field: kept by UploadStash::keep() (every valid new upload is), or, if
     * storing it failed, the upload itself.
     *
     * @return list<UploadedFile|StashedUpload>
     */
    private function upload(Request $request, UploadStash $stash, string $field): array
    {
        $file = $stash->get($field) ?? $request->file($field);

        return $file instanceof UploadedFile || $file instanceof StashedUpload ? [$file] : [];
    }

    /** @return array<string, list<mixed>> */
    private function contactRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-. ]{7,}$/'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'contact_pref' => ['nullable', Rule::in(['phone', 'whatsapp', 'email'])],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function contactFields(array $data): array
    {
        return [
            'name' => trim((string) $data['name']),
            'phone' => trim((string) $data['phone']),
            'email' => strtolower(trim((string) $data['email'])),
            'contact_pref' => $data['contact_pref'] ?? 'phone',
        ];
    }

    /** This form never asks for bank or card numbers, and refuses them if a client sends them. */
    private function refuseBankNumbers(Validator $validator, Request $request): void
    {
        $validator->after(function (Validator $v) use ($request) {
            foreach (['account_number', 'bank_account', 'routing_number', 'card_number', 'pin'] as $field) {
                if ($request->filled($field)) {
                    $v->errors()->add('message', 'Please do not send bank or card numbers. We will never ask for them online.');

                    return;
                }
            }
            // A run of 9 or more digits in free text is almost certainly an account or card number.
            foreach (['message', 'bank_name', 'trading_as', 'business_name', 'position', 'address', 'pharmacist', 'ref1_name', 'ref2_name'] as $field) {
                if (preg_match('/\d[\d\s-]{7,}\d/', input_string($request->input($field))) && preg_match_all('/\d/', input_string($request->input($field))) >= 9) {
                    $v->errors()->add($field, 'Please do not send bank or card numbers. We will never ask for them online.');

                    return;
                }
            }
        });
    }
}
