<?php

namespace Tests\Feature;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Enums\Role;
use App\Models\Lead;
use App\Models\LeadFile;
use App\Models\TradeAccount;
use App\Notifications\AccountOpened;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Trade licences, VAT certificates and IDs open for credit control only; CVs for HR and admin. */
class CreditDocumentsTest extends TestCase
{
    use RefreshDatabase;

    private Lead $application;

    private Lead $cvLead;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->application = Lead::factory()->type(LeadType::Account)->create([
            'business_name' => 'Vieux Fort Pharmacy',
            'details' => ['trade_type' => 'pharmacy', 'trade_type_label' => 'Licensed pharmacy', 'trade_licence_no' => 'SECRET-TL-42', 'vat_registered' => true,
                'vat_no' => 'SECRET-VAT-77', 'pharmacy_licence_no' => 'PH-555', 'district' => 'vieux_fort', 'terms' => 'credit',
                'references' => [['name' => 'Hidden Supplier Ltd', 'phone' => '758-450-1111']]],
        ]);
        $this->attach($this->application, ['trade_licence', 'vat_certificate', 'id', 'pharmacy_licence']);
        $this->cvLead = Lead::factory()->type(LeadType::Application)->create();
        $this->attach($this->cvLead, ['cv']);
    }

    /** @param list<string> $kinds */
    private function attach(Lead $lead, array $kinds): void
    {
        foreach ($kinds as $kind) {
            Storage::disk('local')->put("leads/{$lead->id}/{$kind}.pdf", '%PDF-1.4 test');
            $lead->files()->create(['kind' => $kind, 'path' => "leads/{$lead->id}/{$kind}.pdf", 'original_name' => "{$kind}.pdf", 'mime' => 'application/pdf', 'size' => 14]);
        }
    }

    public function test_credit_control_opens_credit_documents_and_each_open_is_logged(): void
    {
        $officer = $this->staff(Role::Credit);
        foreach ($this->application->files as $file) {
            $this->actingAs($officer)->get(route('admin.leads.file', [$this->application, $file]))->assertOk()->assertDownload();
        }
        $this->assertSame(4, $this->application->notes()->where('body', 'like', 'Opened %')->count());
        $this->actingAs($officer)->get(route('admin.leads.show', $this->application))->assertSee('SECRET-TL-42')->assertSee('Hidden Supplier Ltd');
    }

    public function test_admin_sales_and_hr_cannot_open_credit_documents(): void
    {
        foreach ([Role::Admin, Role::Sales, Role::Hr] as $role) {
            $user = $this->staff($role);
            foreach ($this->application->files as $file) {
                $this->actingAs($user)->get(route('admin.leads.file', [$this->application, $file]))->assertForbidden();
            }
        }
        $this->assertSame(0, $this->application->notes()->where('body', 'like', 'Opened %')->count());
        // Admin sees the figures and a lock on each document.
        $this->actingAs($this->staff(Role::Admin))->get(route('admin.leads.show', $this->application))->assertSee('SECRET-TL-42')->assertSee('Credit control only');
    }

    public function test_sales_sees_the_application_but_not_the_credit_details(): void
    {
        $this->actingAs($this->staff(Role::Sales))->get(route('admin.leads.show', $this->application))->assertOk()
            ->assertSee('Vieux Fort Pharmacy')->assertSee('Credit control only')->assertSee('visible to credit control only')
            ->assertDontSee('SECRET-TL-42')->assertDontSee('SECRET-VAT-77')->assertDontSee('Hidden Supplier Ltd')->assertDontSee('Approve and open account');
    }

    public function test_cvs_open_for_hr_and_admin_only(): void
    {
        $cv = $this->cvLead->files->sole();
        $this->actingAs($this->staff(Role::Hr))->get(route('admin.leads.file', [$this->cvLead, $cv]))->assertOk();
        $this->actingAs($this->staff(Role::Admin))->get(route('admin.leads.file', [$this->cvLead, $cv]))->assertOk();
        $this->actingAs($this->staff(Role::Credit))->get(route('admin.leads.file', [$this->cvLead, $cv]))->assertForbidden();
        $this->actingAs($this->staff(Role::Sales))->get(route('admin.leads.file', [$this->cvLead, $cv]))->assertForbidden();
        $this->actingAs($this->staff(Role::Sales))->get(route('admin.leads.show', $this->cvLead))->assertForbidden();
    }

    public function test_guests_inactive_staff_and_mismatched_files_are_refused(): void
    {
        $file = $this->application->files->first();
        $this->get(route('admin.leads.file', [$this->application, $file]))->assertRedirect('/login');

        $inactive = $this->staff(Role::Credit);
        $inactive->update(['active' => false]);
        $this->actingAs($inactive)->get(route('admin.leads.file', [$this->application, $file]))->assertRedirect('/login');

        $other = Lead::factory()->type(LeadType::Account)->create();
        $this->actingAs($this->staff(Role::Credit))->get(route('admin.leads.file', [$other, $file]))->assertForbidden();
    }

    public function test_documents_are_never_reachable_from_the_web_root(): void
    {
        foreach ($this->application->files as $file) {
            $this->assertFileDoesNotExist(public_path($file->path));
            $this->get('/'.$file->path)->assertNotFound();
            $this->get('/storage/'.$file->path)->assertNotFound();
        }
    }

    public function test_credit_approves_an_application_and_the_customer_gets_an_account_number(): void
    {
        Notification::fake();
        $officer = $this->staff(Role::Credit);
        $this->actingAs($officer)->get('/admin/credit')->assertOk()->assertSee('Vieux Fort Pharmacy');
        $this->actingAs($officer)->post(route('admin.credit.approve', $this->application), ['terms' => 'credit', 'note' => 'Limit agreed'])
            ->assertRedirect(route('admin.leads.show', $this->application));

        $lead = $this->application->fresh();
        $account = TradeAccount::sole();
        $this->assertSame(LeadStatus::Approved, $lead->status);
        $this->assertTrue($lead->tradeAccount->is($account));
        $this->assertMatchesRegularExpression('/^PC-\d{5}$/', $account->number);
        $this->assertSame('PH-555', $account->pharmacy_licence);
        $this->assertTrue($account->canOrderPharmaceuticals());
        Notification::assertSentOnDemand(AccountOpened::class);

        // Approving twice opens nothing new, and an approved application can't be moved back.
        $this->actingAs($officer)->post(route('admin.credit.approve', $lead), ['terms' => 'cod'])
            ->assertRedirect(route('admin.leads.show', $lead))->assertSessionHas('status', fn (string $s) => str_contains($s, 'already approved') && str_contains($s, $account->number));
        Notification::assertSentOnDemandTimes(AccountOpened::class, 1);
        $this->actingAs($officer)->patch(route('admin.leads.update', $lead), ['status' => 'reviewing'])->assertSessionHasErrors('status');
        $this->assertSame(1, TradeAccount::count());
    }

    public function test_two_approvals_racing_open_only_one_account(): void
    {
        Notification::fake();
        $officer = $this->staff(Role::Credit);
        // Both requests loaded the application before either saved (double click, or two officers).
        $first = Lead::findOrFail($this->application->id);
        $second = Lead::findOrFail($this->application->id);

        $this->assertTrue($first->claimForApproval());
        $this->assertFalse($second->claimForApproval());
        $this->assertSame(LeadStatus::Approved, $second->fresh()->status);

        // The controller path for the loser: no account, no email, a calm message.
        $lead = $this->application->fresh();
        $lead->forceFill(['status' => LeadStatus::Reviewing])->saveQuietly();
        $this->actingAs($officer)->post(route('admin.credit.approve', $lead), ['terms' => 'cod'])->assertRedirect();
        $this->actingAs($officer)->post(route('admin.credit.approve', $lead), ['terms' => 'cod'])->assertSessionHas('status', fn (string $s) => str_contains($s, 'already approved'));
        $this->assertSame(1, TradeAccount::count());
        Notification::assertSentOnDemandTimes(AccountOpened::class, 1);
    }

    public function test_the_trade_desk_sees_that_credit_decided_but_not_the_decision_notes(): void
    {
        Notification::fake();
        $this->actingAs($this->staff(Role::Credit))->post(route('admin.credit.approve', $this->application), ['terms' => 'credit', 'note' => 'Limit EC$15,000 agreed, bank reference checked']);
        $number = TradeAccount::sole()->number;

        $this->actingAs($this->staff(Role::Sales))->get(route('admin.leads.show', $this->application))->assertOk()
            ->assertDontSee('Limit EC$15,000')->assertSee('Credit control recorded a decision and opened account '.$number);
        $this->actingAs($this->staff(Role::Credit))->get(route('admin.leads.show', $this->application))->assertSee('Limit EC$15,000 agreed');
    }

    public function test_only_credit_and_admin_decide_on_applications(): void
    {
        $sales = $this->staff(Role::Sales);
        $this->actingAs($sales)->post(route('admin.credit.approve', $this->application), ['terms' => 'cod'])->assertForbidden();
        $this->actingAs($sales)->patch(route('admin.leads.update', $this->application), ['status' => 'declined'])->assertSessionHasErrors('status');
        $this->actingAs($sales)->get('/admin/credit')->assertForbidden();
        $this->actingAs($this->staff(Role::Credit))->patch(route('admin.leads.update', $this->application), ['status' => 'approved'])->assertSessionHasErrors('status');
        $this->assertSame(LeadStatus::New, $this->application->fresh()->status);

        $this->actingAs($this->staff(Role::Admin))->post(route('admin.credit.approve', $this->application), ['terms' => 'cod'])->assertRedirect();
        $this->assertSame(1, TradeAccount::count());
    }

    public function test_credit_document_kinds_are_the_restricted_ones(): void
    {
        foreach (LeadFile::CREDIT_KINDS as $kind) {
            $this->assertTrue((new LeadFile(['kind' => $kind]))->isCreditDocument());
        }
        $this->assertFalse((new LeadFile(['kind' => 'deck']))->isCreditDocument());
        $this->assertTrue((new LeadFile(['kind' => 'cv']))->isHrDocument());
    }
}
