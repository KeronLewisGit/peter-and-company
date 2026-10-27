<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

/** The brand portfolio by division, from config/peterco.php (sourced brands only). */
class BrandController extends Controller
{
    public function index(): View
    {
        return view('pages.brands.index', [
            'counts' => Product::listed()->selectRaw('division, count(*) as total')->groupBy('division')->pluck('total', 'division'),
        ]);
    }

    public function show(string $division): View
    {
        $d = peterco('divisions.'.$division);
        abort_unless(is_array($d), 404);

        return view('pages.brands.show', [
            'key' => $division,
            'division' => $d,
            'products' => empty($d['restricted']) ? Product::listed()->where('division', $division)->orderByDesc('featured')->orderBy('brand')->orderBy('name')->limit(8)->get() : collect(),
        ]);
    }
}
