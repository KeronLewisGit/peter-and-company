<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\PhotoResizer;
use App\Support\Slug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** The trade catalogue. Sales and administrators. No price or stock fields, on purpose. */
class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $q = input_string($request->query('q'));
        $division = input_string($request->query('division'));

        return view('admin.products.index', [
            'products' => Product::query()
                ->when(array_key_exists($division, Product::publicDivisions()), fn (Builder $b) => $b->where('division', $division))
                ->when($q !== '', function (Builder $b) use ($q) {
                    $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
                    $b->where(fn (Builder $w) => $w->where('name', 'like', $like)->orWhere('brand', 'like', $like)->orWhere('sku', 'like', $like));
                })
                ->orderBy('division')->orderBy('brand')->orderBy('name')->paginate(40)->withQueryString(),
            'filters' => ['q' => $q, 'division' => $division],
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', ['product' => new Product(['published' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $product = new Product($data);
        $product->slug = Slug::unique(Product::class, $data['brand'].' '.$data['name'].' '.($data['pack_size'] ?? ''), 'product');
        $this->photo($request, $product);
        $product->save();

        return to_route('admin.products.index')->with('status', "{$product->name} added to the catalogue.");
    }

    public function edit(Product $product): View
    {
        return view('admin.products.form', ['product' => $product]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->fill($this->validated($request));
        if ($request->boolean('remove_photo')) {
            $this->deletePhoto($product);
        }
        $this->photo($request, $product);
        $product->save();

        return to_route('admin.products.index')->with('status', "{$product->name} saved.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->deletePhoto($product);
        $product->delete();

        return to_route('admin.products.index')->with('status', 'Product removed from the catalogue.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:140'],
            'brand' => ['required', 'string', 'max:80'],
            'division' => ['required', Rule::in(array_keys(Product::publicDivisions()))],
            'category' => ['nullable', 'string', 'max:60'],
            'pack_size' => ['nullable', 'string', 'max:80'],
            'sku' => ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'published' => ['nullable', 'boolean'],
            'featured' => ['nullable', 'boolean'],
        ], ['division.in' => 'Choose a division. The pharmaceutical range is never listed in the public catalogue.']);
        unset($data['photo']);
        $data['published'] = $request->boolean('published');
        $data['featured'] = $request->boolean('featured');

        return $data;
    }

    private function photo(Request $request, Product $product): void
    {
        $file = $request->file('photo');
        if (! $file) {
            return;
        }
        $this->deletePhoto($product);
        $stored = PhotoResizer::store($file, 'products');
        $product->photo_path = $stored['path'];
        $product->thumb_path = $stored['thumb_path'];
    }

    private function deletePhoto(Product $product): void
    {
        Storage::disk('uploads')->delete(array_filter([$product->photo_path, $product->thumb_path]));
        $product->photo_path = null;
        $product->thumb_path = null;
    }
}
