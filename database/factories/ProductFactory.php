<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Sample product '.fake()->unique()->numberBetween(1, 99999);

        return [
            'slug' => Str::slug($name),
            'name' => $name,
            'brand' => 'Carib',
            'division' => 'beverages',
            'category' => 'Beer & stout',
            'pack_size' => '24 × 275 ml',
            'sku' => null,
            'published' => true,
            'featured' => false,
            'is_demo' => true,
        ];
    }
}
