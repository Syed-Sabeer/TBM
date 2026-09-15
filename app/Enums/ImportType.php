<?php

namespace App\Enums;

enum ImportType: string
{
    case Stock = 'stock';
    case Cost = 'cost';
    case RateCard = 'rate_card';
    case Catalog = 'catalog';

    public function label(): string
    {
        return match ($this) {
            self::Stock => 'Stock levels',
            self::Cost => 'Mill cost / FOB',
            self::RateCard => 'Customer rate card',
            self::Catalog => 'Product catalog',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Stock => 'The daily sheet from the mill: quantity on hand per parent SKU, colour and warehouse.',
            self::Cost => 'Landed cost per parent SKU. Feeds margin reporting — never shown to customers.',
            self::RateCard => 'A negotiated price file loaded against one account, overriding its tier.',
            self::Catalog => 'New parent SKUs, descriptions, sizes and colourways.',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $c) => [$c->value => $c->label()]
        )->all();
    }
}
