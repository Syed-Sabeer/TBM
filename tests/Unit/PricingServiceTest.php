<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Company;
use App\Models\PriceOverride;
use App\Models\PriceTier;
use App\Models\Product;
use App\Services\Pricing\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The formula, pinned:
 *
 *     base × quantity break × tier × negotiated override
 *
 * Every figure below is worked out by hand in the assertion comment, so a
 * change in behaviour shows up as a failing arithmetic claim rather than as a
 * number nobody can check.
 */
class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    private PricingService $pricing;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pricing = app(PricingService::class);
        $this->product = Product::factory()
            ->for(Category::factory())
            ->create(['base_price' => 2.00, 'moq' => 50]);
    }

    public function test_the_smallest_break_is_the_base_price(): void
    {
        $quote = $this->pricing->quote($this->product, null, 50);

        // 2.00 × 1.00 × 1.00 × 1.00
        $this->assertSame(2.0, $quote->unitPrice);
    }

    public function test_the_quantity_break_is_the_largest_one_reached(): void
    {
        // 260 pieces sits on the 250 break, not the 500 one.
        $this->assertSame(0.87, $this->pricing->breakFactorFor(260));
        $this->assertSame(250, $this->pricing->breakQuantityFor(260));

        // Exactly on a break takes that break.
        $this->assertSame(0.82, $this->pricing->breakFactorFor(500));
    }

    public function test_below_the_first_break_nobody_gets_a_discount(): void
    {
        $this->assertSame(1.0, $this->pricing->breakFactorFor(10));
    }

    public function test_the_tier_multiplies_the_break_price(): void
    {
        $company = Company::factory()->create([
            'price_tier_id' => PriceTier::factory()->create(['factor' => 0.86])->id,
        ]);

        $quote = $this->pricing->quote($this->product, $company, 1000);

        // 2.00 × 0.77 × 0.86 = 1.3244
        $this->assertSame(1.3244, $quote->unitPrice);
    }

    public function test_a_negotiated_rate_applies_on_top_of_the_tier(): void
    {
        $company = Company::factory()->create([
            'price_tier_id' => PriceTier::factory()->create(['factor' => 0.94])->id,
        ]);

        PriceOverride::create([
            'company_id' => $company->id,
            'product_id' => $this->product->id,
            'factor' => 0.90,
        ]);

        $quote = $this->pricing->quote($this->product, $company->fresh(), 250);

        // 2.00 × 0.87 × 0.94 × 0.90 = 1.47204 → 1.472 at four places
        $this->assertSame(1.472, round($quote->unitPrice, 4));
        $this->assertTrue($quote->hasNegotiatedRate());
    }

    public function test_an_override_only_affects_the_account_it_belongs_to(): void
    {
        $tier = PriceTier::factory()->create(['factor' => 1.00]);
        $favoured = Company::factory()->create(['price_tier_id' => $tier->id]);
        $ordinary = Company::factory()->create(['price_tier_id' => $tier->id]);

        PriceOverride::create([
            'company_id' => $favoured->id,
            'product_id' => $this->product->id,
            'factor' => 0.80,
        ]);

        $this->assertSame(1.6, $this->pricing->quote($this->product, $favoured->fresh(), 50)->unitPrice);
        $this->assertSame(2.0, $this->pricing->quote($this->product, $ordinary->fresh(), 50)->unitPrice);
    }

    public function test_the_ladder_covers_every_configured_break(): void
    {
        $ladder = $this->pricing->ladder($this->product, null);

        $this->assertCount(count(config('tbm.quantity_breaks')), $ladder);
        $this->assertSame(50, $ladder->first()->quantity);

        // Prices only ever fall as the quantity rises.
        $previous = PHP_FLOAT_MAX;

        foreach ($ladder as $break) {
            $this->assertLessThanOrEqual($previous, $break->unitPrice);
            $previous = $break->unitPrice;
        }
    }

    public function test_the_next_break_prompt_reports_the_shortfall(): void
    {
        $next = $this->pricing->nextBreak($this->product, null, 190);

        $this->assertSame(250, $next['quantity']);
        $this->assertSame(60, $next['shortfall']);
        $this->assertGreaterThan(0, $next['saving_percent']);
    }

    public function test_there_is_no_next_break_past_the_last_one(): void
    {
        $this->assertNull($this->pricing->nextBreak($this->product, null, 5000));
    }

    public function test_margin_is_measured_against_the_landed_cost(): void
    {
        $product = Product::factory()
            ->for(Category::factory())
            ->create(['base_price' => 2.00, 'cost_price' => 1.00]);

        $quote = $this->pricing->quote($product, null, 50);

        // (2.00 − 1.00) / 2.00
        $this->assertSame(50.0, $quote->marginPercent());
    }
}
