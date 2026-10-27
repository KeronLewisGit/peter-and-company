<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Vacancy;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home', [
            'featured' => Product::listed()->orderByDesc('featured')->orderBy('division')->orderBy('name')->limit(8)->get(),
            'productCount' => Product::listed()->count(),
            'openVacancies' => Vacancy::open()->count(),
        ]);
    }

    public function about(): View
    {
        return view('pages.about');
    }

    public function contact(): View
    {
        return view('pages.contact');
    }

    public function privacy(): View
    {
        return view('pages.privacy');
    }

    public function credits(): View
    {
        return view('pages.credits');
    }

    public function sitemapPage(): View
    {
        return view('pages.sitemap', ['vacancies' => Vacancy::open()->orderBy('title')->get(['slug', 'title'])]);
    }

    public function sitemap(): Response
    {
        $static = ['home', 'brands.index', 'catalogue', 'order.show', 'account.create', 'principals', 'careers.index', 'careers.apply',
            'about', 'contact', 'privacy', 'credits', 'sitemap.page'];
        $urls = collect($static)->map(fn (string $name) => ['loc' => route($name), 'lastmod' => null])
            ->concat(collect(array_keys((array) peterco('divisions')))->map(fn (string $d) => ['loc' => route('brands.show', $d), 'lastmod' => null]))
            ->concat(Vacancy::open()->get()->map(fn (Vacancy $v) => ['loc' => route('careers.show', $v), 'lastmod' => $v->updated_at?->toAtomString()]));

        return response()->view('sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml');
    }
}
