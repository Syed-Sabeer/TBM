<?php

/*
|--------------------------------------------------------------------------
| TBM domain configuration
|--------------------------------------------------------------------------
|
| Commercial rules that the whole application reads from one place, so a
| policy change is a config edit rather than a hunt through controllers.
|
*/

return [

    /*
     | Quantity breaks. The factor is applied to the item's base price before
     | the account's tier and any negotiated item override.
     */
    'quantity_breaks' => [
        ['quantity' => 50,   'factor' => 1.00],
        ['quantity' => 100,  'factor' => 0.93],
        ['quantity' => 250,  'factor' => 0.87],
        ['quantity' => 500,  'factor' => 0.82],
        ['quantity' => 1000, 'factor' => 0.77],
        ['quantity' => 2500, 'factor' => 0.72],
    ],

    /*
     | Gross margin floor, as a percentage. A price below this needs sign-off
     | and is flagged everywhere it appears in the back office.
     */
    'margin_floor' => (float) env('TBM_MARGIN_FLOOR', 28),

    /*
     | Fallback FOB cost as a fraction of the base price, used for items that
     | have not yet had a cost imported from the mill.
     */
    'cost_ratio' => (float) env('TBM_COST_RATIO', 0.52),

    /*
     | The seeded accounts are a sample of the real customer base. Demand is
     | scaled by this factor so weeks-of-cover and reorder alerts read true.
     | Set to 1 once real shipped quantities are flowing.
     */
    'demand_scale' => (float) env('TBM_DEMAND_SCALE', 12),

    /*
     | Decoration methods.
     |
     | None of these carry a price here on purpose. Decoration is quoted per
     | colour and per location by a rep once artwork is in, and lands on the
     | order as decoration_total. The storefront captures the intent only.
     */
    'decoration' => [
        'default' => 'Blank (undecorated)',
        'methods' => [
            ['name' => 'Blank (undecorated)', 'note' => 'Ships from stock'],
            ['name' => 'Screen print',        'note' => '1–6 spot colours'],
            ['name' => 'DTF transfer',        'note' => 'Full colour, no set-up'],
            ['name' => 'Embroidery',          'note' => 'Up to 15k stitches'],
            ['name' => 'Sublimation',         'note' => 'Poly bodies only', 'requires_material' => 'Poly'],
        ],
    ],

    /*
     | Storefront behaviour.
     */
    'storefront' => [
        'per_page' => 12,
        'require_po_over' => 5000,
        'free_freight_over' => 7500,
        'freight_rate' => 0.045,
    ],

    /*
     | Company contact details used across the storefront and documents.
     */
    'company' => [
        'legal_name' => 'TBM — Tote Bag Market',
        'phone' => '+1 (800) 555-0123',
        'email' => 'sales@tbm.com',
        'art_email' => 'art@tbm.com',
        'accounts_email' => 'accounts@tbm.com',
        'compliance_email' => 'compliance@tbm.com',
        'address' => "1420 E 15th St\nLos Angeles, CA 90021",
        'hours' => 'Mon–Fri, 7am–5pm PT',
    ],

    /*
     | Footer credit.
     */
    'powered_by' => [
        'name' => 'Deveon Inc',
        'url' => 'https://deveoninc.com/',
    ],
];
