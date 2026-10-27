<?php

namespace Tests\Feature;

use App\Enums\LeadType;
use App\Models\Lead;
use App\Models\Vacancy;
use App\Notifications\LeadReceived;
use App\Notifications\NewLead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeadFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Notification::fake();
        config(['peterco.notify' => 'office@example.com']);
    }

    /** @return array<string, mixed> */
    private function account(array $overrides = []): array
    {
        static $n = 1000;

        return array_merge($this->contact(['phone' => '758-452-'.$n++]), [
            'position' => 'Owner', 'business_name' => 'Rodney Bay Minimart Ltd', 'trade_type' => 'minimart', 'trade_licence_no' => 'TL-1234',
            'vat_registered' => 'yes', 'vat_no' => 'VAT-998877', 'years_trading' => '3_10', 'address' => 'Rodney Bay', 'district' => 'gros_islet',
            'terms' => 'cod', 'consent' => '1', 'declaration' => '1',
            'trade_licence' => UploadedFile::fake()->create('licence.pdf', 120, 'application/pdf'),
            'vat_certificate' => UploadedFile::fake()->create('vat.pdf', 80, 'application/pdf'),
            'id_document' => UploadedFile::fake()->image('id.jpg'),
        ], $overrides);
    }

    public function test_trade_account_application_stores_documents_privately(): void
    {
        $this->post('/trade-account', $this->account())->assertRedirect('/thanks')->assertSessionHasNoErrors();
        $lead = Lead::sole();
        $this->assertSame(LeadType::Account, $lead->type);
        $this->assertMatchesRegularExpression('/^TA-\d{2}-\d{5}$/', $lead->reference);
        $this->assertNotNull($lead->consented_at);
        $this->assertEqualsCanonicalizing(['trade_licence', 'vat_certificate', 'id'], $lead->files->pluck('kind')->all());
        foreach ($lead->files as $f) {
            Storage::disk('local')->assertExists($f->path);
            $this->assertStringStartsWith("leads/{$lead->id}/", $f->path);
        }
        Notification::assertSentOnDemand(NewLead::class, function (NewLead $n, array $channels, $notifiable) {
            $body = implode("\n", $n->toMail($notifiable)->introLines);

            return ! str_contains($body, 'VAT-998877') && ! str_contains($body, 'TL-1234');
        });
        Notification::assertSentOnDemand(LeadReceived::class);
    }

    public function test_documents_attached_on_a_failed_try_are_kept_for_the_next_one(): void
    {
        // A typo elsewhere on the form: the documents were fine, and the browser can't re-fill file inputs.
        $this->post('/trade-account', $this->account(['phone' => 'call me']))->assertSessionHasErrors('phone');
        $this->assertSame(0, Lead::count());
        $this->get('/trade-account')->assertOk()->assertSee('Attached: licence.pdf')->assertSee('Attached: vat.pdf')->assertSee('Attached: id.jpg');
        $this->assertCount(3, Storage::disk('local')->files('stash'));

        // Second try: the ID is replaced, the rest is reused, nothing is attached twice.
        $this->post('/trade-account', $this->account(['trade_licence' => null, 'vat_certificate' => null, 'id_document' => UploadedFile::fake()->image('passport.jpg')]))
            ->assertRedirect('/thanks')->assertSessionHasNoErrors();
        $lead = Lead::sole();
        $this->assertEqualsCanonicalizing(['licence.pdf', 'vat.pdf', 'passport.jpg'], $lead->files->pluck('original_name')->all());
        foreach ($lead->files as $f) {
            Storage::disk('local')->assertExists($f->path);
            $this->assertStringStartsWith("leads/{$lead->id}/", $f->path);
        }
        $this->assertSame([], Storage::disk('local')->files('stash'));
        $this->get('/trade-account')->assertDontSee('Attached:');

        // A kept file never satisfies the form for someone else's session.
        $this->flushSession();
        $this->post('/trade-account', $this->account(['trade_licence' => null]))->assertSessionHasErrors('trade_licence');
    }

    public function test_a_kept_cv_carries_over_and_abandoned_uploads_are_deleted_after_a_day(): void
    {
        $base = array_merge($this->contact(), ['district' => 'castries', 'available' => 'now', 'driving_licence' => 'none', 'consent' => '1']);
        $this->post('/careers/apply', $base + ['cv' => UploadedFile::fake()->create('my-cv.pdf', 100, 'application/pdf')])->assertSessionHasErrors('role_interest');
        $this->get('/careers/apply')->assertSee('Attached: my-cv.pdf');
        $this->post('/careers/apply', $base + ['role_interest' => 'Driver'])->assertRedirect('/thanks');
        $this->assertSame('my-cv.pdf', Lead::sole()->files->sole()->original_name);

        Storage::disk('local')->put('stash/old.pdf', 'x');
        touch(Storage::disk('local')->path('stash/old.pdf'), now()->subDays(2)->getTimestamp());
        $this->flushSession();
        $this->post('/careers/apply', $base + ['cv' => UploadedFile::fake()->create('cv2.pdf', 10, 'application/pdf')]);
        Storage::disk('local')->assertMissing('stash/old.pdf');
    }

    public function test_vat_credit_and_pharmacy_rules(): void
    {
        $this->post('/trade-account', $this->account(['vat_no' => '', 'vat_certificate' => null]))->assertSessionHasErrors(['vat_no', 'vat_certificate']);
        $this->post('/trade-account', $this->account(['terms' => 'credit']))->assertSessionHasErrors(['ref1_name', 'ref1_phone']);
        $this->post('/trade-account', $this->account(['trade_type' => 'pharmacy']))->assertSessionHasErrors(['pharmacy_licence_no', 'pharmacist', 'pharmacy_licence']);
        $this->post('/trade-account', $this->account(['consent' => null, 'declaration' => null]))->assertSessionHasErrors(['consent', 'declaration']);
        $this->post('/trade-account', $this->account(['id_document' => UploadedFile::fake()->create('id.exe', 10)]))->assertSessionHasErrors('id_document');
        $this->assertSame(0, Lead::count());

        $this->post('/trade-account', $this->account(['vat_registered' => 'no', 'vat_no' => '', 'vat_certificate' => null]))->assertSessionHasNoErrors();
        $this->post('/trade-account', $this->account([
            'trade_type' => 'pharmacy', 'pharmacy_licence_no' => 'PH-77', 'pharmacist' => 'R. Joseph',
            'pharmacy_licence' => UploadedFile::fake()->create('ph.pdf', 50, 'application/pdf'),
        ]))->assertSessionHasNoErrors();
        $this->assertSame('PH-77', Lead::latest('id')->first()->detail('pharmacy_licence_no'));
    }

    public function test_bank_account_numbers_are_refused(): void
    {
        $this->post('/trade-account', $this->account(['message' => 'Our account number is 1234567890123', 'bank_name' => 'Bank 001234567890']))
            ->assertSessionHasErrors();
        // Any free-text box, not only the message and bank name.
        $this->post('/trade-account', $this->account(['address' => 'Rodney Bay, acct 4012 8888 8888 1881']))->assertSessionHasErrors('address');
        $this->post('/trade-account', $this->account(['terms' => 'credit', 'ref1_name' => 'Card 4111-1111-1111-1111', 'ref1_phone' => '758-450-1111']))->assertSessionHasErrors('ref1_name');
        $this->assertSame(0, Lead::count());
    }

    public function test_principal_enquiry_with_a_deck(): void
    {
        $this->post('/principals', array_merge($this->contact(), [
            'business_name' => 'Island Snacks Ltd', 'job_title' => 'Export Manager', 'country' => 'Trinidad and Tobago',
            'company_site' => 'https://example.com', 'brands' => 'Sample Chips', 'categories' => ['food', 'beverages'],
            'message' => 'We would like to talk about distribution.', 'deck' => UploadedFile::fake()->create('deck.pdf', 300, 'application/pdf'),
        ]))->assertRedirect('/thanks')->assertSessionHasNoErrors();
        $lead = Lead::sole();
        $this->assertStringStartsWith('PR-', $lead->reference);
        $this->assertSame(['food', 'beverages'], $lead->detail('categories'));
        $this->assertSame('deck', $lead->files->sole()->kind);

        $this->post('/principals', array_merge($this->contact(), ['business_name' => 'X', 'job_title' => 'Y', 'country' => 'Z', 'brands' => 'B',
            'categories' => ['pharmaceutical-x'], 'message' => 'Hi']))->assertSessionHasErrors('categories.0');
    }

    public function test_job_application_with_cv_for_a_vacancy_and_general(): void
    {
        $vacancy = Vacancy::factory()->create();
        $base = array_merge($this->contact(), ['district' => 'castries', 'available' => 'now', 'driving_licence' => 'commercial', 'consent' => '1',
            'cv' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf')]);

        $this->get('/careers/apply?vacancy='.$vacancy->slug)->assertOk()->assertSee($vacancy->title);
        $this->post('/careers/apply', $base + ['vacancy' => $vacancy->slug])->assertRedirect('/thanks')->assertSessionHasNoErrors();
        $lead = Lead::sole();
        $this->assertStringStartsWith('CV-', $lead->reference);
        $this->assertTrue($lead->vacancy->is($vacancy));
        $this->assertSame('cv', $lead->files->sole()->kind);

        $this->post('/careers/apply', array_merge($base, ['cv' => UploadedFile::fake()->create('cv.docx', 100)]))->assertSessionHasErrors('role_interest');
        $this->flushSession(); // a new visitor, with no CV kept from an earlier try
        $this->post('/careers/apply', array_merge($base, ['role_interest' => 'Driver', 'cv' => null]))->assertSessionHasErrors('cv');
        $this->post('/careers/apply', array_merge($base, ['role_interest' => 'Driver', 'cv' => UploadedFile::fake()->image('cv.png')]))->assertSessionHasErrors('cv');
        $this->post('/careers/apply', array_merge($base, ['role_interest' => 'Driver', 'cv' => UploadedFile::fake()->create('cv.pdf', 50, 'application/pdf')]))->assertSessionHasNoErrors();
        $this->assertSame(2, Lead::count());
    }

    public function test_applications_to_closed_vacancies_are_refused(): void
    {
        $closed = Vacancy::factory()->create(['closes_on' => today()->subDay()]);
        $this->post('/careers/apply', array_merge($this->contact(), ['vacancy' => $closed->slug, 'district' => 'castries', 'available' => 'now',
            'driving_licence' => 'none', 'consent' => '1', 'cv' => UploadedFile::fake()->create('cv.pdf', 50, 'application/pdf')]))->assertSessionHasErrors('vacancy');
        $this->assertSame(0, Lead::count());
    }

    public function test_contact_enquiry_and_thanks_page(): void
    {
        $this->post('/contact', $this->contact(['topic' => 'sponsorship', 'message' => 'Do you sponsor events?']))->assertRedirect('/thanks');
        $lead = Lead::sole();
        $this->assertStringStartsWith('EN-', $lead->reference);
        $this->get('/thanks')->assertOk()->assertSee($lead->reference);
        $this->post('/contact', $this->contact(['topic' => 'nonsense', 'message' => 'x']))->assertSessionHasErrors('topic');
    }

    public function test_one_phone_number_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/contact', $this->contact(['topic' => 'other', 'message' => 'Hello '.$i]))->assertRedirect('/thanks');
        }
        $this->post('/contact', $this->contact(['topic' => 'other', 'message' => 'Again']))->assertStatus(429);
        $this->assertSame(5, Lead::count());
    }

    public function test_bots_get_a_thank_you_but_nothing_is_stored(): void
    {
        foreach (['/contact' => ['topic' => 'other', 'message' => 'x'], '/principals' => [], '/trade-account' => [], '/careers/apply' => []] as $url => $extra) {
            $this->post($url, $this->contact($extra + ['website' => 'http://spam.example']))->assertRedirect('/thanks');
        }
        $this->assertSame(0, Lead::count());
        Notification::assertNothingSent();
    }
}
