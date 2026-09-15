<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = \App\Models\Product::class;

    public function definition(): array
    {
        $name = Str::headline(fake()->words(3, true)).' Tote';

        return [
            // The two identifiers are deliberately unrelated strings, exactly
            // as they are in the real catalogue.
            'parent_sku' => 'BPK'.fake()->unique()->numberBetween(100, 999),
            'sku' => 'DS'.fake()->unique()->numberBetween(1000, 9999),

            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'description' => fake()->paragraph(),
            'shape' => 'tote',
            'material' => 'Cotton Canvas',
            'fabric_weight' => '8 oz',
            'sizes' => [['label' => '15"W x 16"H x 4"D']],
            'flags' => [],
            'base_price' => fake()->randomFloat(2, 1.2, 9.5),
            'cost_price' => null,
            'moq' => 100,
            'carton_quantity' => 100,
            'order_step' => 25,
            'origin' => 'Imported',
            'is_published' => true,
            'position' => 0,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
