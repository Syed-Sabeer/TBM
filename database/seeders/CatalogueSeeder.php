<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Colourway;
use App\Models\InventoryLevel;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Colourways, categories, warehouses and the catalogue itself, from
 * database/data/catalogue.php.
 *
 * Everything is upserted on its natural key, so running this again after
 * editing the data file updates rather than duplicates — and never touches a
 * stock figure that has been corrected since, because opening quantities are
 * only written when the level is created.
 */
class CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        $data = require database_path('data/catalogue.php');

        $this->colourways($data['colourways']);
        $this->categories($data['categories']);
        $this->warehouses($data['warehouses']);
        $this->products($data['products']);
    }

    private function colourways(array $rows): void
    {
        foreach ($rows as $row) {
            Colourway::updateOrCreate(['slug' => $row['slug']], $row);
        }

        $this->command?->info(sprintf('  %d colourways', count($rows)));
    }

    private function categories(array $rows): void
    {
        foreach ($rows as $row) {
            Category::updateOrCreate(['slug' => $row['slug']], $row);
        }

        $this->command?->info(sprintf('  %d categories', count($rows)));
    }

    private function warehouses(array $rows): void
    {
        foreach ($rows as $row) {
            Warehouse::updateOrCreate(['code' => $row['code']], $row);
        }

        $this->command?->info(sprintf('  %d warehouses', count($rows)));
    }

    private function products(array $rows): void
    {
        $categories = Category::pluck('id', 'slug');
        $colourways = Colourway::pluck('id', 'slug');
        $warehouses = Warehouse::pluck('id', 'code');

        foreach ($rows as $row) {
            $product = Product::updateOrCreate(
                ['sku' => $row['sku']],
                [
                    'parent_sku' => $row['parent_sku'],
                    'category_id' => $categories[$row['category']],
                    'name' => $row['name'],
                    'slug' => Str::slug($row['sku'].'-'.$row['name']),
                    'description' => $row['description'],
                    'shape' => $row['shape'],
                    'material' => $row['material'],
                    'fabric_weight' => $row['fabric_weight'],
                    'sizes' => array_map(fn (string $size) => ['label' => $size], $row['sizes']),
                    'flags' => $row['flags'],
                    'base_price' => $row['base_price'],

                    // No cost has been imported yet, so margin reporting falls
                    // back to the configured ratio until the first cost file.
                    'cost_price' => round($row['base_price'] * config('tbm.cost_ratio'), 4),

                    'moq' => $row['moq'],
                    'order_step' => $row['moq'] >= 500 ? 250 : ($row['moq'] >= 250 ? 50 : 25),
                    'carton_quantity' => max(100, $row['moq']),
                    'origin' => 'Imported',
                    'is_published' => true,
                    'position' => $row['position'],
                ]
            );

            $product->colourways()->sync(
                collect($row['colours'])
                    ->filter(fn (string $slug) => isset($colourways[$slug]))
                    ->mapWithKeys(fn (string $slug, int $i) => [
                        $colourways[$slug] => ['is_active' => true, 'position' => $i],
                    ])
                    ->all()
            );

            foreach ($row['stock'] as $code => $quantity) {
                if (! isset($warehouses[$code])) {
                    continue;
                }

                InventoryLevel::firstOrCreate(
                    ['product_id' => $product->id, 'warehouse_id' => $warehouses[$code]],
                    ['on_hand' => $quantity, 'synced_at' => now()],
                );
            }
        }

        $this->command?->info(sprintf('  %d products', count($rows)));
    }
}
