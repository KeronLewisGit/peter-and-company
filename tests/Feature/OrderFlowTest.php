<?php

namespace Tests\Feature;

use App\Enums\AccountStatus;
use App\Enums\LeadType;
use App\Models\Lead;
use App\Models\Product;
use App\Models\TradeAccount;
use App\Notifications\LeadReceived;
use App\Notifications\NewLead;
use App\Support\Delivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function order(array $overrides = []): array
    {
        return array_merge($this->contact(), [
            'business_name' => 'Gros Islet Minimart', 'trade_type' => 'minimart', 'district' => 'gros_islet',
            'delivery_address' => 'Dauphin Street, Gros Islet',
        ], $overrides);
    }

    public function test_building_a_list_and_sending_it_creates_a_referenced_request(): void
    {
        Notification::fake();
        config(['peterco.notify' => 'desk@example.com']);
        $beer = Product::factory()->create(['name' => 'Carib Lager']);
        $cola = Product::factory()->create(['name' => 'Pepsi Cola', 'brand' => 'Pepsi', 'category' => 'Soft drinks']);

        $this->post('/order/list', ['product' => $beer->slug, 'cases' => 5])->assertRedirect();
        $this->post('/order/list', ['product' => $cola->slug, 'cases' => 2]);
        $this->post('/order/list', ['product' => $beer->slug, 'cases' => 3]); // adds to the line
        $this->get('/order')->assertSee('Carib Lager')->assertSee('Pepsi Cola')->assertSee('10 cases');

        $day = Delivery::days()[0]->toDateString();
        $this->post('/order', $this->order(['cases' => [$beer->id => 8, $cola->id => 2], 'delivery_date' => $day, 'other_items' => 'Ice, 20 bags']))
            ->assertRedirect('/thanks')->assertSessionHasNoErrors();

        $lead = Lead::sole();
        $this->assertSame(LeadType::Order, $lead->type);
        $this->assertMatchesRegularExpression('/^OR-\d{2}-\d{5}$/', $lead->reference);
        $this->assertSame(10, $lead->caseCount());
        $this->assertSame($day, $lead->delivery_date?->toDateString());
        $this->assertSame('Ice, 20 bags', $lead->detail('other_items'));
        $this->get('/thanks')->assertSee($lead->reference);
        $this->get('/order')->assertSee('Your list is empty');

        Notification::assertSentOnDemand(NewLead::class);
        Notification::assertSentOnDemand(LeadReceived::class, fn ($n, $c, $notifiable) => $notifiable->routes['mail'] === 'shanice@example.com');
    }

    public function test_the_list_can_be_updated_and_lines_removed_without_javascript(): void
    {
        $p = Product::factory()->create();
        $q = Product::factory()->create(['name' => 'Other Line']);
        $this->post('/order/list', ['product' => $p->slug]);
        $this->post('/order/list', ['product' => $q->slug]);
        $this->post('/order/list/update', ['cases' => [$p->id => 12, $q->id => 0]])->assertRedirect('/order');
        $this->get('/order')->assertSee('12 cases')->assertDontSee('Other Line');
        $this->post('/order/list/remove', ['product_id' => $p->id]);
        $this->get('/order')->assertSee('Your list is empty');
    }

    public function test_updating_or_removing_lines_keeps_the_details_already_typed(): void
    {
        $p = Product::factory()->create();
        $q = Product::factory()->create(['name' => 'Other Line']);
        $this->post('/order/list', ['product' => $p->slug]);
        $this->post('/order/list', ['product' => $q->slug]);
        $typed = ['name' => 'Shanice Joseph', 'business_name' => 'Gros Islet Minimart', 'delivery_address' => 'Bay Street, by the jetty', 'message' => 'Gate code 4411'];

        $this->post('/order/list/update', $typed + ['cases' => [$p->id => 7, $q->id => 2]])->assertRedirect('/order');
        $this->get('/order')->assertSee('Shanice Joseph')->assertSee('Bay Street, by the jetty')->assertSee('Gate code 4411')->assertSee('value="7"', false);

        $this->post('/order/list/remove', $typed + ['product_id' => $q->id, 'cases' => [$p->id => 7, $q->id => 2]])->assertRedirect('/order');
        $this->get('/order')->assertSee('Gros Islet Minimart')->assertSee('Shanice Joseph')->assertDontSee('Other Line');
    }

    public function test_hidden_and_pharmaceutical_lines_cannot_be_added(): void
    {
        $hidden = Product::factory()->create(['published' => false]);
        $rx = Product::factory()->create(['division' => 'pharmaceutical', 'name' => 'Tablets']);
        $this->post('/order/list', ['product' => $hidden->slug])->assertNotFound();
        $this->post('/order/list', ['product' => $rx->slug])->assertNotFound();
    }

    public function test_empty_orders_bad_accounts_and_closed_days_are_refused(): void
    {
        $this->post('/order', $this->order())->assertSessionHasErrors('cases');
        $this->post('/order', $this->order(['other_items' => 'x', 'account_number' => 'PC-99999']))->assertSessionHasErrors('account_number');
        $held = TradeAccount::factory()->create(['status' => AccountStatus::OnHold]);
        $this->post('/order', $this->order(['other_items' => 'x', 'account_number' => $held->number]))->assertSessionHasErrors('account_number');
        $sunday = today()->next('Sunday')->toDateString();
        $this->post('/order', $this->order(['other_items' => 'x', 'delivery_date' => $sunday]))->assertSessionHasErrors('delivery_date');
        $this->post('/order', $this->order(['other_items' => 'x', 'delivery_date' => today()->addDays(40)->toDateString()]))->assertSessionHasErrors('delivery_date');
        $this->assertSame(0, Lead::count());
    }

    public function test_an_account_number_links_the_order_in_any_format(): void
    {
        $account = TradeAccount::factory()->create();
        $digits = preg_replace('/\D/', '', $account->number);
        $this->post('/order', $this->order(['other_items' => '5 cases water', 'account_number' => strtolower('pc '.$digits)]))->assertRedirect('/thanks');
        $this->assertTrue(Lead::sole()->tradeAccount->is($account));
    }

    public function test_pharmaceuticals_only_from_an_active_licensed_account(): void
    {
        $shop = TradeAccount::factory()->create();
        $this->post('/order', $this->order(['pharmaceutical' => 1, 'other_items' => 'Panadol, 4 cases']))->assertSessionHasErrors('pharmaceutical');
        $this->post('/order', $this->order(['pharmaceutical' => 1, 'other_items' => 'Panadol, 4 cases', 'account_number' => $shop->number]))->assertSessionHasErrors('pharmaceutical');

        $pharmacy = TradeAccount::factory()->pharmacy()->create();
        $this->post('/order', $this->order(['trade_type' => 'pharmacy', 'pharmaceutical' => 1, 'account_number' => $pharmacy->number]))->assertSessionHasErrors('other_items');
        $this->post('/order', $this->order(['trade_type' => 'pharmacy', 'pharmaceutical' => 1, 'other_items' => 'Panadol, 4 cases', 'account_number' => $pharmacy->number]))
            ->assertRedirect('/thanks')->assertSessionHasNoErrors();
        $this->assertTrue((bool) Lead::sole()->detail('pharmaceutical'));
    }

    public function test_the_honeypot_drops_bots_silently(): void
    {
        $this->post('/order', $this->order(['other_items' => 'x', 'website' => 'http://spam.example']))->assertRedirect('/thanks');
        $this->assertSame(0, Lead::count());
    }
}
