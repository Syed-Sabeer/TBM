<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    protected $model = \App\Models\Category::class;

    public function definition(): array
    {
        $name = Str::headline(fake()->unique()->words(2, true));

        return [
            'slug' => Str::slug($name),
            'name' => $name,
            'group' => 'Totes',
            'blurb' => fake()->sentence(),
            'shape' => 'tote',
            'position' => 0,
        ];
    }
}
