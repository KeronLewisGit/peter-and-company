<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Enums\Role;
use App\Models\Lead;
use App\Models\Product;
use App\Models\TradeAccount;
use App\Models\User;
use App\Models\Vacancy;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        foreach (['/admin', '/admin/leads', '/admin/credit', '/admin/accounts', '/admin/products', '/admin/vacancies', '/admin/users'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_staff_sign_in_and_inactive_accounts_cannot(): void
    {
        $user = User::factory()->role(Role::Sales)->create(['email' => 'rep@peterco.test']);
        $this->post('/login', ['email' => 'REP@peterco.test', 'password' => 'password'])->assertRedirect('/admin');
        $this->post('/logout');
        $user->update(['active' => false]);
        $this->post('/login', ['email' => 'rep@peterco.test', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->actingAs($user)->get('/admin')->assertRedirect('/login');
    }

    public function test_dashboard_leads_with_requests_past_their_own_target(): void
    {
        $sales = $this->staff(Role::Sales);
        $late = Lead::factory()->type(LeadType::Order)->create(['business_name' => 'Late Order Bar']);
        $late->forceFill(['created_at' => now()->subHours(3)])->save();
        $account = Lead::factory()->type(LeadType::Account)->create();
        $account->forceFill(['created_at' => now()->subHours(3)])->save(); // target is 48 hours, so not late
        $cv = Lead::factory()->type(LeadType::Application)->create();
        $cv->forceFill(['created_at' => now()->subDays(10)])->save(); // late, but sales doesn't see it

        $this->assertSame(2, Lead::overdue()->count());
        $this->actingAs($sales)->get('/admin')->assertOk()->assertSee('01')->assertSee('request is past the response target')->assertSee('Late Order Bar');
        $this->actingAs($sales)->get('/admin/leads?overdue=1')->assertSee('Late Order Bar')->assertDontSee($account->reference);
    }

    public function test_roles_only_see_their_request_types(): void
    {
        $leads = collect(LeadType::cases())->mapWithKeys(fn (LeadType $t) => [$t->value => Lead::factory()->type($t)->create()]);
        foreach (Role::cases() as $role) {
            $user = $this->staff($role);
            $inbox = $this->actingAs($user)->get('/admin/leads')->assertOk();
            foreach ($leads as $type => $lead) {
                $sees = in_array(LeadType::from($type), $role->leadTypes(), true);
                $sees ? $inbox->assertSee($lead->reference) : $inbox->assertDontSee($lead->reference);
                $this->actingAs($user)->get(route('admin.leads.show', $lead))->assertStatus($sees ? 200 : 403);
            }
        }
    }

    public function test_area_access_by_role(): void
    {
        $matrix = [
            '/admin/credit' => [Role::Admin, Role::Credit],
            '/admin/accounts' => [Role::Admin, Role::Sales, Role::Credit],
            '/admin/products' => [Role::Admin, Role::Sales],
            '/admin/vacancies' => [Role::Admin, Role::Hr],
            '/admin/users' => [Role::Admin],
        ];
        foreach (Role::cases() as $role) {
            $user = $this->staff($role);
            foreach ($matrix as $url => $allowed) {
                $this->actingAs($user)->get($url)->assertStatus(in_array($role, $allowed, true) ? 200 : 403);
            }
        }
    }

    public function test_order_workflow_notes_and_delivery_day(): void
    {
        $sales = $this->staff(Role::Sales);
        $lead = Lead::factory()->type(LeadType::Order)->create();
        $this->actingAs($sales)->post(route('admin.leads.note', $lead), ['body' => 'Called, prices confirmed', 'contacted' => '1'])->assertRedirect();
        $lead->refresh();
        $this->assertSame(LeadStatus::Confirmed, $lead->status);
        $this->assertNotNull($lead->first_contacted_at);

        $this->actingAs($sales)->patch(route('admin.leads.update', $lead), ['status' => 'dispatched', 'assigned_to' => $sales->id, 'delivery_date' => '2026-11-03']);
        $lead->refresh();
        $this->assertSame(LeadStatus::Dispatched, $lead->status);
        $this->assertSame('2026-11-03', $lead->delivery_date?->toDateString());
        $this->assertTrue($lead->notes()->where('body', 'like', 'Delivery day set%')->exists());

        // A status from another workflow is refused.
        $this->actingAs($sales)->patch(route('admin.leads.update', $lead), ['status' => 'shortlisted'])->assertSessionHasErrors('status');
        // Someone who can't see orders can't be assigned one.
        $hr = $this->staff(Role::Hr);
        $this->actingAs($sales)->patch(route('admin.leads.update', $lead), ['assigned_to' => $hr->id])->assertSessionHasErrors('assigned_to');
    }

    public function test_csv_export_respects_role_and_neutralises_formulas(): void
    {
        Lead::factory()->type(LeadType::Order)->create(['name' => '=HYPERLINK("x")']);
        $cv = Lead::factory()->type(LeadType::Application)->create();
        $csv = $this->actingAs($this->staff(Role::Sales))->get('/admin/leads/export')->assertOk()->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString($cv->reference, $csv);
    }

    public function test_trade_accounts_are_managed_by_credit_and_read_by_sales(): void
    {
        $account = TradeAccount::factory()->create(['business_name' => 'Choiseul Grocery']);
        $this->actingAs($this->staff(Role::Sales))->get('/admin/accounts')->assertOk()->assertSee('Choiseul Grocery')->assertDontSee('Add an existing customer');
        $this->actingAs($this->staff(Role::Sales))->patch(route('admin.accounts.update', $account), ['status' => 'on_hold', 'terms' => 'cod'])->assertForbidden();

        $credit = $this->staff(Role::Credit);
        $this->actingAs($credit)->patch(route('admin.accounts.update', $account), ['status' => 'on_hold', 'terms' => 'credit'])->assertRedirect();
        $this->assertSame(AccountStatus::OnHold, $account->fresh()->status);
        $this->actingAs($credit)->post('/admin/accounts', ['business_name' => 'Old Customer Ltd', 'trade_type' => 'supermarket', 'contact_name' => 'A. Pierre',
            'phone' => '758-454-0000', 'terms' => 'cod'])->assertSessionHasNoErrors();
        $this->assertTrue(TradeAccount::where('business_name', 'Old Customer Ltd')->exists());
        $this->actingAs($credit)->get('/admin/accounts')->assertSee('Old Customer Ltd'); // also clears the flash message
        $this->actingAs($credit)->get('/admin/accounts?q=Choiseul')->assertSee('Choiseul Grocery')->assertDontSee('Old Customer Ltd');
    }

    public function test_catalogue_crud_has_no_price_fields_and_refuses_the_pharma_division(): void
    {
        Storage::fake('uploads');
        $sales = $this->staff(Role::Sales);
        $this->actingAs($sales)->get('/admin/products/create')->assertOk()->assertDontSee('name="price"', false);
        $this->actingAs($sales)->post('/admin/products', ['brand' => 'Grace', 'name' => 'Grace Coconut Milk', 'division' => 'food', 'pack_size' => '24 × 400 ml',
            'published' => '1', 'photo' => UploadedFile::fake()->image('pack.jpg', 1200, 900)])->assertRedirect('/admin/products');
        $product = Product::sole();
        $this->assertNotNull($product->photo_path);
        Storage::disk('uploads')->assertExists($product->photo_path);
        $this->get('/catalogue')->assertSee('Grace Coconut Milk');

        $this->actingAs($sales)->put(route('admin.products.update', $product), ['brand' => 'Grace', 'name' => 'Grace Coconut Milk', 'division' => 'pharmaceutical'])
            ->assertSessionHasErrors('division');
        $this->actingAs($sales)->put(route('admin.products.update', $product), ['brand' => 'Grace', 'name' => 'Grace Coconut Milk', 'division' => 'food', 'remove_photo' => '1'])
            ->assertSessionHasNoErrors();
        $this->assertNull($product->fresh()->photo_path);
        $this->get('/catalogue')->assertDontSee('Grace Coconut Milk'); // unpublished when the box is unticked
        $this->actingAs($sales)->delete(route('admin.products.destroy', $product))->assertRedirect();
        $this->assertSame(0, Product::count());
    }

    public function test_hr_manages_vacancies(): void
    {
        $hr = $this->staff(Role::Hr);
        $this->actingAs($hr)->post('/admin/vacancies', ['title' => 'Delivery Driver', 'department' => 'Logistics', 'location' => 'Cul De Sac', 'employment_type' => 'Full time',
            'summary' => 'Deliver orders island-wide.', 'duties' => "Drive\nUnload", 'published' => '1'])->assertRedirect('/admin/vacancies');
        $vacancy = Vacancy::sole();
        $this->assertSame('delivery-driver', $vacancy->slug);
        $this->get('/careers')->assertSee('Delivery Driver');
        $this->actingAs($hr)->put(route('admin.vacancies.update', $vacancy), ['title' => 'Delivery Driver', 'department' => 'Logistics', 'location' => 'Cul De Sac',
            'employment_type' => 'Full time', 'summary' => 'x'])->assertRedirect();
        $this->get('/careers')->assertDontSee('Delivery Driver');
    }

    public function test_admin_manages_staff_but_cannot_lock_themselves_out(): void
    {
        $admin = $this->staff(Role::Admin);
        $this->actingAs($admin)->post('/admin/users', ['name' => 'New Clerk', 'email' => 'NEW@peterco.test', 'role' => 'credit', 'password' => 'a-long-password'])->assertSessionHasNoErrors();
        $clerk = User::where('email', 'new@peterco.test')->sole();
        $this->assertSame(Role::Credit, $clerk->role);
        $this->actingAs($admin)->patch(route('admin.users.update', $clerk), ['role' => 'hr', 'active' => '0']);
        $this->assertFalse($clerk->fresh()->active);
        $this->actingAs($admin)->patch(route('admin.users.update', $admin), ['role' => 'sales', 'active' => '1'])->assertSessionHasErrors('role');
        $this->assertSame(Role::Admin, $admin->fresh()->role);
    }

    public function test_every_admin_link_opens_for_the_role_that_sees_it(): void
    {
        Storage::fake('local');
        $this->seed(DemoSeeder::class);
        foreach (Role::cases() as $role) {
            $user = $this->staff($role);
            foreach (['/admin', '/admin/leads', '/admin/credit', '/admin/accounts', '/admin/products', '/admin/vacancies'] as $page) {
                $res = $this->actingAs($user)->get($page);
                if ($res->status() !== 200) {
                    continue;
                }
                preg_match_all('#href="'.preg_quote(url('/admin'), '#').'(/[a-z]+)?["?/]#', (string) $res->getContent(), $m);
                foreach (array_unique($m[1]) as $area) {
                    $this->actingAs($user)->get('/admin'.$area)->assertOk();
                }
            }
        }
    }

    public function test_demo_seeder_builds_a_clearly_marked_demo(): void
    {
        Storage::fake('local');
        $this->seed(DemoSeeder::class);
        foreach (['admin', 'sales', 'credit', 'hr'] as $r) {
            $this->assertTrue(User::where('email', "{$r}@peterco.test")->exists());
        }
        $this->assertTrue(Product::where('is_demo', false)->doesntExist());
        $this->assertTrue(Vacancy::where('is_demo', false)->doesntExist());
        $this->assertTrue(TradeAccount::where('is_demo', false)->doesntExist());
        $this->assertTrue(Product::where('division', 'pharmaceutical')->doesntExist());
        $brands = collect(config('peterco.divisions'))->pluck('brands')->flatten()->all();
        $this->assertSame([], Product::pluck('brand')->unique()->diff($brands)->values()->all(), 'Demo products only use sourced brands');
        $this->assertGreaterThan(0, Lead::overdue()->count());
        $this->assertTrue(TradeAccount::where('trade_type', 'pharmacy')->where('status', 'active')->exists());
        $this->actingAs(User::where('email', 'admin@peterco.test')->sole())->get('/admin')->assertOk();
        $this->get('/catalogue')->assertSee('Sample listing');
        $this->get('/careers')->assertSee('Sample vacancy');
    }

    public function test_database_seeder_only_creates_the_first_admin_from_env(): void
    {
        config(['peterco.admin' => ['name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'a-long-password']]);
        $this->seed();
        $this->assertSame(1, User::count());
        $this->assertSame(Role::Admin, User::sole()->role);
        $this->assertSame(0, Lead::count() + Product::count());
    }
}
