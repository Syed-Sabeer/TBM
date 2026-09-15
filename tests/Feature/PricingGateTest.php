<?php

namespace Tests\Feature;

use App\Enums\CompanyUserRole;
use App\Models\Category;
use App\Models\Company;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The rule the whole business model rests on: no price is visible until an
 * account has been approved.
 *
 * These tests exercise the rendered pages rather than the helper methods,
 * because the failure mode that matters is a figure appearing in HTML — not a
 * method returning the wrong boolean.
 */
class PricingGateTest extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->product = Product::factory()
            ->for(Category::factory())
            ->create(['base_price' => 4.00, 'moq' => 100, 'sku' => 'DS9999', 'parent_sku' => 'BPK999']);
    }

    public function test_a_signed_out_visitor_sees_no_price(): void
    {
        $response = $this->get(route('product', $this->product));

        $response->assertOk();
        $response->assertSee('Sign in for your price');
        $response->assertDontSee('$4.00');
    }

    public function test_a_signed_out_visitor_still_sees_the_catalogue_and_stock(): void
    {
        // The goods and the quantities are public; only the money is not.
        $this->get(route('shop'))
            ->assertOk()
            ->assertSee($this->product->sku)
            ->assertSee($this->product->name);
    }

    public function test_an_unapproved_account_sees_no_price(): void
    {
        $user = $this->customerOn(Company::factory()->pending());

        $response = $this->actingAs($user)->get(route('product', $this->product));

        $response->assertOk();
        $response->assertSee('Pricing opens when your account is approved');
        $response->assertDontSee('$4.00');
    }

    public function test_an_account_on_hold_loses_pricing_again(): void
    {
        $user = $this->customerOn(Company::factory()->onHold());

        $this->actingAs($user)
            ->get(route('product', $this->product))
            ->assertDontSee('$4.00');
    }

    public function test_a_lapsed_resale_certificate_stops_pricing(): void
    {
        $company = Company::factory()->create([
            'certificate_status' => 'verified',
            'certificate_expires_at' => now()->subDay(),
        ]);

        $this->actingAs($this->customerOn($company))
            ->get(route('product', $this->product))
            ->assertDontSee('$4.00');
    }

    public function test_an_approved_account_sees_its_own_price(): void
    {
        $tier = PriceTier::factory()->create(['code' => 'B', 'factor' => 0.94]);
        $company = Company::factory()->create(['price_tier_id' => $tier->id]);

        $response = $this->actingAs($this->customerOn($company))
            ->get(route('product', $this->product));

        // 4.00 base × 0.93 at the 100 break × 0.94 tier = 3.4968
        $response->assertOk();
        $response->assertSee('$3.4968');
    }

    public function test_two_accounts_see_different_prices_for_the_same_item(): void
    {
        $a = Company::factory()->create(['price_tier_id' => PriceTier::factory()->create(['code' => 'A', 'factor' => 0.86])->id]);
        $c = Company::factory()->create(['price_tier_id' => PriceTier::factory()->create(['code' => 'C', 'factor' => 1.00])->id]);

        $this->actingAs($this->customerOn($a))
            ->get(route('product', $this->product))
            ->assertSee('$3.1992');   // 4.00 × 0.93 × 0.86

        $this->actingAs($this->customerOn($c))
            ->get(route('product', $this->product))
            ->assertSee('$3.72');     // 4.00 × 0.93 × 1.00
    }

    public function test_the_quote_endpoint_refuses_an_unapproved_account(): void
    {
        $user = $this->customerOn(Company::factory()->pending());

        $this->actingAs($user)
            ->getJson(route('product.quote', $this->product).'?quantity=500')
            ->assertForbidden();
    }

    private function customerOn($company): User
    {
        $company = $company instanceof Company ? $company : $company->create();

        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole(CompanyUserRole::Buyer->value);

        return $user;
    }
}
