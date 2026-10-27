<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\OrderList;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** The trade catalogue: GET filters (division, brand, search), no prices, no stock levels. */
class CatalogueController extends Controller
{
    public function index(Request $request): View
    {
        $divisions = Product::publicDivisions();
        $division = input_string($request->query('division'));
        $division = array_key_exists($division, $divisions) ? $division : null;
        $brand = input_string($request->query('brand')) ?: null;
        $q = mb_substr(input_string($request->query('q')), 0, 80) ?: null;

        $base = Product::listed()->when($division, fn (Builder $b, string $d) => $b->where('division', $d));
        $brands = (clone $base)->distinct()->orderBy('brand')->pluck('brand');
        $brand = $brand && $brands->contains($brand) ? $brand : null;

        $products = (clone $base)
            ->when($brand, fn (Builder $b, string $v) => $b->where('brand', $v))
            ->when($q, function (Builder $b, string $term) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
                $b->where(fn (Builder $w) => $w->where('name', 'like', $like)->orWhere('brand', 'like', $like)->orWhere('category', 'like', $like)->orWhere('sku', 'like', $like));
            })
            ->orderBy('division')->orderBy('brand')->orderBy('name')
            ->paginate(24)->withQueryString();

        return view('pages.catalogue', [
            'products' => $products,
            'divisions' => $divisions,
            'brands' => $brands,
            'filters' => ['division' => $division, 'brand' => $brand, 'q' => $q],
            'counts' => Product::listed()->selectRaw('division, count(*) as total')->groupBy('division')->pluck('total', 'division'),
            'onList' => OrderList::items(),
        ]);
    }
}
