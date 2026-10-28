<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The motion layer is an enhancement: the server-rendered page must be complete without JavaScript
 * and with reduced motion. These tests pin the markup the CSS and script rely on.
 */
class MotionMarkupTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_map_renders_every_district_with_its_days_as_text(): void
    {
        $response = $this->get('/')->assertOk();
        $districts = (array) config('peterco.districts');
        $map = (array) config('peterco.delivery_map.districts');
        $this->assertSame(array_keys($districts), array_values(array_intersect(array_keys($districts), array_keys($map))), 'Every district has a delivery-map entry');
        $shapes = require resource_path('data/st-lucia-districts.php');

        foreach ($districts as $key => $name) {
            $this->assertNotEmpty($shapes[$key] ?? null, "Map outline for {$key}");
            $response->assertSee('data-district="'.$key.'"', false);
            // Focusable on the map, with its name and days as the accessible label.
            $response->assertSee('tabindex="0" role="img" aria-label="'.e($name).': delivery', false);
        }
        $response->assertSee('<li data-district="castries" data-days="Monday to Friday"', false)
            ->assertSee('Monday to Friday')->assertSee('Tuesday and Thursday');
    }

    public function test_sample_delivery_days_are_flagged_while_they_are_a_placeholder(): void
    {
        $this->get('/')->assertSee('Sample schedule')->assertSee('to be replaced with the trade desk');

        config(['peterco.delivery_map.placeholder' => false]);
        $this->get('/')->assertDontSee('Sample schedule');
    }

    public function test_map_licence_is_credited(): void
    {
        $this->get('/photo-credits')->assertOk()->assertSee('Delivery map')->assertSee('NordNordWest')
            ->assertSee('CC BY-SA 3.0 DE')->assertSee('https://commons.wikimedia.org/wiki/File:Castries_in_Saint_Lucia_2014.svg', false);
    }

    public function test_hero_headline_and_stats_are_complete_without_javascript(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        // The word-by-word headline still reads as one heading.
        $this->assertMatchesRegularExpression('/<h1[^>]*>.*<\/h1>/s', $html);
        preg_match('/<h1[^>]*>(.*?)<\/h1>/s', $html, $m);
        $this->assertSame("St Lucia's shelves, stocked from Cul De Sac.", trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($m[1]), ENT_QUOTES))));
        // Count-up tiles carry their final value in the HTML.
        $this->assertStringContainsString('data-count="1891" style="min-width: 4ch" >1891</span>', $html);
        $this->assertStringContainsString('data-count="4"', $html);
    }

    public function test_marquee_copies_are_hidden_from_assistive_technology_and_focus(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $this->assertSame(2, substr_count($html, 'class="marquee-row"'));
        // One real, labelled list per row, and one inert copy that exists only for the seamless loop.
        $this->assertSame(2, substr_count($html, 'aria-hidden="true" inert data-copy'));
        $this->assertStringContainsString('aria-label="Featured brands"', $html);
        foreach ((array) config('peterco.featured_brands') as $brand) {
            $this->assertStringContainsString('alt="'.e($brand).'"', $html);
        }
        // The pause control is rendered hidden: the script reveals it only when the logos move.
        $this->assertStringContainsString('data-marquee-toggle aria-pressed="false" hidden', $html);
    }

    public function test_add_to_order_still_posts_and_redirects_without_javascript(): void
    {
        $product = Product::factory()->create(['name' => 'Carib Lager', 'brand' => 'Carib', 'division' => 'beverages']);
        $this->from('/catalogue')->post('/order/list', ['product' => $product->slug, 'cases' => 3])
            ->assertRedirect('/catalogue#p-'.$product->slug);
        // The page the script reads after the post: the new count and the flash message are marked up.
        $this->get('/catalogue')->assertSee('data-order-count>1<', false)->assertSee('data-flash', false)
            ->assertSee('Added Carib Lager to your order list.');
    }

    public function test_trade_account_stepper_links_to_every_part_of_the_form(): void
    {
        $html = $this->get('/trade-account')->assertOk()->getContent();
        foreach (range(1, 5) as $n) {
            $this->assertStringContainsString('id="step-'.$n.'"', $html);
            $this->assertStringContainsString('href="#step-'.$n.'"', $html);
        }
        $this->assertStringContainsString('aria-label="Application progress"', $html);
    }
}
