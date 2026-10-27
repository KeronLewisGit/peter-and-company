<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * The order list a trade customer builds while browsing the catalogue: product id => cases,
 * kept in the session (no account needed). Turned into an order request at /order.
 */
final class OrderList
{
    private const string KEY = 'order_list';

    public const int MAX_CASES = 999;

    public const int MAX_LINES = 60;

    /** @return array<int, int> */
    public static function items(): array
    {
        $raw = session(self::KEY, []);
        $items = [];
        foreach (is_array($raw) ? $raw : [] as $id => $cases) {
            if (is_numeric($id) && is_numeric($cases) && (int) $cases > 0) {
                $items[(int) $id] = min(self::MAX_CASES, (int) $cases);
            }
        }

        return $items;
    }

    public static function add(Product $product, int $cases): void
    {
        $items = self::items();
        if (! isset($items[$product->id]) && count($items) >= self::MAX_LINES) {
            return;
        }
        $items[$product->id] = min(self::MAX_CASES, ($items[$product->id] ?? 0) + max(1, $cases));
        session([self::KEY => $items]);
    }

    /** @param array<int, int> $items */
    public static function replace(array $items): void
    {
        session([self::KEY => array_slice(array_filter($items, fn (int $c) => $c > 0), 0, self::MAX_LINES, true)]);
    }

    public static function remove(int $productId): void
    {
        $items = self::items();
        unset($items[$productId]);
        session([self::KEY => $items]);
    }

    public static function clear(): void
    {
        session()->forget(self::KEY);
    }

    public static function count(): int
    {
        return count(self::items());
    }

    /**
     * The listed products on the list, in the order they were added, with their cases.
     * Products that were unpublished in the meantime drop out.
     *
     * @return Collection<int, array{product: Product, cases: int}>
     */
    public static function lines(): Collection
    {
        $items = self::items();
        if ($items === []) {
            return collect();
        }
        $products = Product::listed()->whereIn('id', array_keys($items))->get()->keyBy('id');

        return collect($items)
            ->filter(fn (int $cases, int $id) => $products->has($id))
            ->map(fn (int $cases, int $id) => ['product' => $products[$id], 'cases' => $cases])
            ->values();
    }
}
