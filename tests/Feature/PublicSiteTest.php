<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Vacancy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_public_page_opens(): void
    {
        Product::factory()->create(['name' => 'Carib Lager', 'featured' => true]);
        $vacancy = Vacancy::factory()->create(['title' => 'Merchandiser']);
        foreach (['/', '/brands', '/brands/wines-spirits', '/brands/beverages', '/brands/food', '/brands/pharmaceutical', '/catalogue', '/order',
            '/trade-account', '/principals', '/careers', '/careers/apply', '/careers/'.$vacancy->slug, '/about', '/contact', '/privacy',
            '/photo-credits', '/sitemap', '/login'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')->assertSee(route('catalogue'), false);
        $this->get('/brands/unknown')->assertNotFound();
        $this->get('/thanks')->assertRedirect('/');
    }

    public function test_home_shows_the_company_facts_from_config(): void
    {
        $this->get('/')->assertSee('Acado St. Lucia')->assertSee('Peter &amp; Company', false)->assertSee('1891')
            ->assertSee(format_phone('758-457-7000'));
    }

    public function test_every_inner_page_uses_the_shared_hero(): void
    {
        $vacancy = Vacancy::factory()->create();
        foreach (['/brands', '/brands/food', '/catalogue', '/order', '/trade-account', '/principals', '/careers', '/careers/apply',
            '/careers/'.$vacancy->slug, '/about', '/contact', '/privacy', '/photo-credits', '/sitemap', '/no-such-page'] as $url) {
            $this->get($url)->assertSee('data-hero="page"', false);
        }
    }

    public function test_catalogue_filters_and_never_shows_prices_or_pharmaceuticals(): void
    {
        Product::factory()->create(['name' => 'Carib Lager', 'brand' => 'Carib', 'division' => 'beverages']);
        Product::factory()->create(['name' => 'Hennessy V.S', 'brand' => 'Hennessy', 'division' => 'wines-spirits', 'category' => 'Cognac & brandy']);
        Product::factory()->create(['name' => 'Hidden Line', 'published' => false]);
        Product::factory()->create(['name' => 'Restricted Tablets', 'division' => 'pharmaceutical']);

        $this->get('/catalogue')->assertSee('Carib Lager')->assertSee('Hennessy V.S')
            ->assertDontSee('Hidden Line')->assertDontSee('Restricted Tablets')->assertDontSee('EC$');
        $this->get('/catalogue?division=wines-spirits')->assertSee('Hennessy V.S')->assertDontSee('Carib Lager');
        $this->get('/catalogue?q=carib')->assertSee('Carib Lager')->assertDontSee('Hennessy V.S');
        $this->get('/catalogue?division=pharmaceutical')->assertOk()->assertDontSee('Restricted Tablets');
        $this->get('/brands/pharmaceutical')->assertSee('licensed')->assertDontSee('Restricted Tablets');
    }

    public function test_brand_pages_list_only_configured_brands_as_text(): void
    {
        $brands = (array) config('peterco.divisions.wines-spirits.brands');
        $res = $this->get('/brands/wines-spirits')->assertOk();
        foreach (array_slice($brands, 0, 5) as $b) {
            $res->assertSee(e($b), false);
        }
    }

    public function test_brand_logo_tiles_exist_for_configured_brands_and_are_credited(): void
    {
        $configured = collect((array) config('peterco.divisions'))->flatMap(fn (array $d) => $d['brands'])->all();
        foreach ((array) config('brand-logos') as $brand => $file) {
            $this->assertContains($brand, $configured, "Logo for an unknown brand: {$brand}");
            $this->assertFileExists(public_path("img/brands/{$file}.webp"));
        }
        $this->assertNull(brand_logo('Not A Real Brand'));
        $this->get('/brands/wines-spirits')->assertSee(brand_logo('Hennessy'), false)->assertSee('Hennessy');
        $this->get('/photo-credits')->assertOk()->assertSee('trademarks of their owners');
    }

    public function test_closed_and_hidden_vacancies_are_not_listed(): void
    {
        Vacancy::factory()->create(['title' => 'Open Role']);
        $closed = Vacancy::factory()->create(['title' => 'Closed Role', 'closes_on' => today()->subDay()]);
        Vacancy::factory()->create(['title' => 'Hidden Role', 'published' => false]);
        $this->get('/careers')->assertSee('Open Role')->assertDontSee('Closed Role')->assertDontSee('Hidden Role');
        $this->get('/careers/'.$closed->slug)->assertNotFound();
    }

    public function test_security_headers_are_sent(): void
    {
        $this->get('/')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options');
    }

    public function test_deploy_defaults_use_st_lucia_time_and_the_new_name(): void
    {
        // Without APP_TIMEZONE in .env, delivery days and response targets must still run on St Lucia time.
        $this->assertStringContainsString("env('APP_TIMEZONE', 'America/St_Lucia')", (string) file_get_contents(config_path('app.php')));
        $env = (string) file_get_contents(base_path('deploy/env.production.example'));
        $this->assertStringContainsString('APP_TIMEZONE=America/St_Lucia', $env);
        // Customer emails are sent "from" APP_NAME: the company was renamed in February 2025.
        $this->assertStringContainsString('APP_NAME="Acado St. Lucia"', $env);
        $this->assertStringContainsString('APP_URL=https://acadostlucia.com', $env);
        $this->assertStringNotContainsString('peterandcompanydistribution', $env);
        // The demo seeder (staff with the password "password") never ships in the Hostinger zip.
        $this->assertStringContainsString("--exclude 'database/seeders/DemoSeeder.php'", (string) file_get_contents(base_path('scripts/package-hostinger.sh')));
    }

    public function test_facts_match_the_sources(): void
    {
        // The timeline heading counts from 1891 rather than a number typed in a given year.
        $this->get('/about')->assertOk()->assertSee((now()->year - 1891).' years in six dates.');
        // Brands from the division pages' logo grids (rechecked 27 Oct 2026); "Children's DPH" is not on them.
        $this->get('/brands/wines-spirits')->assertSee('Campari')->assertSee('Santa Margherita');
        $this->get('/brands/food')->assertSee('DAK');
        $this->get('/brands/pharmaceutical')->assertSee('DPH')->assertDontSee('Children&#039;s DPH', false);
    }
}
