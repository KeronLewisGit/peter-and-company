<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Vacancy;
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

    public function test_careers_is_linked_outside_the_about_drop_down_and_on_the_home_page(): void
    {
        $careers = route('careers.index');
        $html = $this->get('/')->assertOk()->getContent();

        // Header: strip the "About us" drop-down, then the main nav must still link to Careers.
        preg_match('/<nav class="hidden items-center lg:flex" aria-label="Main">(.*?)<\/nav>/s', $html, $main);
        $withoutDrops = preg_replace('/<div class="nav-drop">.*?<\/div>/s', '', $main[1]);
        $this->assertStringContainsString('href="'.$careers.'" class="nav-link"', $withoutDrops);
        // The orange top bar, and a top-level row in the mobile menu.
        $this->assertStringContainsString('href="'.$careers.'" class="hidden min-h-11 items-center gap-1.5 px-2 font-semibold sm:inline-flex" data-topbar-careers', $html);
        preg_match('/<nav class="mnav[^"]*" aria-label="Mobile">(.*?)<\/nav>/s', $html, $mobile);
        $this->assertStringContainsString('<a href="'.$careers.'" >Careers</a>', preg_replace('/<div class="sub">.*?<\/div>/s', '', $mobile[1]));

        // The home page careers band.
        $this->assertStringContainsString('aria-labelledby="careers-title"', $html);
    }

    public function test_home_careers_band_counts_open_vacancies_or_asks_for_a_cv(): void
    {
        $this->get('/')->assertSee('Send your CV')->assertSee('No open vacancies today')->assertDontSee('open vacancies right now');

        Vacancy::factory()->count(2)->create();
        Vacancy::factory()->create(['published' => false]);
        $open = Vacancy::open()->count();
        $this->get('/')->assertSee('data-count="'.$open.'"', false)->assertSee('open vacancies right now')
            ->assertSee(route('careers.index').'#vacancies', false);
        $this->get('/careers')->assertSee('See open vacancies')->assertSee('Send a general application')->assertSee('id="vacancies"', false);
    }

    public function test_careers_nav_item_is_current_on_careers_pages(): void
    {
        $current = '/href="'.preg_quote(route('careers.index'), '/').'" class="nav-link"\s+aria-current="page"/';
        $this->assertMatchesRegularExpression($current, $this->get('/careers')->getContent());
        $this->assertMatchesRegularExpression($current, $this->get('/careers/apply')->getContent());
        $this->assertDoesNotMatchRegularExpression($current, $this->get('/')->getContent());
    }
}
