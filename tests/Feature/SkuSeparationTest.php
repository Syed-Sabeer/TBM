<?php

namespace Tests\Feature;

use App\Enums\CompanyUserRole;
use App\Enums\StaffRole;
use App\Models\Category;
use App\Models\Company;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The mill reference must never reach a customer.
 *
 * "BPK18 on the excel file, we would like to call it DS4500, and the customer
 * should see all with DS4500 — even their packing slip and invoice."
 *
 * Every customer-facing surface is checked here, because this is the kind of
 * rule that survives review and then leaks through one forgotten column in one
 * table six months later.
 */
class SkuSeparationTest extends TestCase
{
    use RefreshDatabase;

    private const MILL_REFERENCE = 'BPK18';

    private const ITEM_NUMBER = 'DS4500';

    private Product $product;

    private Company $company;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->product = Product::factory()
            ->for(Category::factory())
            ->create(['parent_sku' => self::MILL_REFERENCE, 'sku' => self::ITEM_NUMBER]);

        $this->company = Company::factory()->create();

        $this->customer = User::factory()->create(['company_id' => $this->company->id]);
        $this->customer->assignRole(CompanyUserRole::Buyer->value);
    }

    public function test_the_item_page_shows_the_item_number_and_not_the_mill_reference(): void
    {
        $response = $this->get(route('product', $this->product));

        $response->assertSee(self::ITEM_NUMBER);
        $response->assertDontSee(self::MILL_REFERENCE);
    }

    public function test_the_catalogue_listing_hides_the_mill_reference(): void
    {
        $this->get(route('shop'))
            ->assertSee(self::ITEM_NUMBER)
            ->assertDontSee(self::MILL_REFERENCE);
    }

    public function test_a_signed_in_customer_never_sees_the_mill_reference(): void
    {
        $this->actingAs($this->customer)
            ->get(route('product', $this->product))
            ->assertDontSee(self::MILL_REFERENCE);
    }

    public function test_a_customer_cannot_search_the_catalogue_by_mill_reference(): void
    {
        // Otherwise the search box becomes a lookup table for our sourcing.
        $this->actingAs($this->customer)
            ->get(route('shop', ['q' => self::MILL_REFERENCE]))
            ->assertDontSee(self::ITEM_NUMBER);
    }

    public function test_the_invoice_carries_the_item_number_only(): void
    {
        $order = $this->orderWithLine();

        $this->actingAs($this->customer)
            ->get(route('account.orders.invoice', $order))
            ->assertSee(self::ITEM_NUMBER)
            ->assertDontSee(self::MILL_REFERENCE);
    }

    public function test_the_packing_slip_carries_the_item_number_only(): void
    {
        $order = $this->orderWithLine();

        $this->actingAs($this->customer)
            ->get(route('account.orders.packing-slip', $order))
            ->assertSee(self::ITEM_NUMBER)
            ->assertDontSee(self::MILL_REFERENCE);
    }

    public function test_the_customer_order_page_carries_the_item_number_only(): void
    {
        $order = $this->orderWithLine();

        $this->actingAs($this->customer)
            ->get(route('account.orders.show', $order))
            ->assertSee(self::ITEM_NUMBER)
            ->assertDontSee(self::MILL_REFERENCE);
    }

    public function test_a_customer_cannot_open_the_pick_list(): void
    {
        $order = $this->orderWithLine();

        $this->actingAs($this->customer)
            ->get(route('admin.orders.pick-list', $order))
            ->assertRedirect();
    }

    public function test_staff_do_see_the_mill_reference_where_they_need_it(): void
    {
        $order = $this->orderWithLine();
        $staff = User::factory()->create(['company_id' => null]);
        $staff->assignRole(StaffRole::Owner->value);

        // The pick list is picked by mill reference, so it leads with it.
        $this->actingAs($staff)
            ->get(route('admin.orders.pick-list', $order))
            ->assertSee(self::MILL_REFERENCE)
            ->assertSee(self::ITEM_NUMBER);
    }

    public function test_the_order_line_freezes_both_identifiers(): void
    {
        $order = $this->orderWithLine();

        // Re-source the item from a different mill.
        $this->product->update(['parent_sku' => 'XYZ99']);

        $item = $order->items()->first();

        // The historical document still reprints exactly as it was issued.
        $this->assertSame(self::MILL_REFERENCE, $item->parent_sku);
        $this->assertSame(self::ITEM_NUMBER, $item->sku);
    }

    private function orderWithLine(): Order
    {
        $order = Order::create([
            'reference' => 'TBM-2026-0001',
            'company_id' => $this->company->id,
            'placed_by_id' => $this->customer->id,
            'status' => \App\Enums\OrderStatus::Delivered,
            'merchandise_total' => 500,
            'grand_total' => 500,
            'total_pieces' => 100,
            'placed_at' => now()->subWeek(),
            'ship_to' => ['street' => '1 Test Way', 'city' => 'Los Angeles', 'state' => 'CA', 'postcode' => '90021'],
        ]);

        $order->items()->create([
            'product_id' => $this->product->id,
            'sku' => $this->product->sku,
            'parent_sku' => $this->product->parent_sku,
            'name' => $this->product->name,
            'colour_name' => 'Natural',
            'size' => '15"W x 16"H',
            'decoration' => 'Blank (undecorated)',
            'quantity' => 100,
            'unit_price' => 5.00,
            'line_total' => 500,
        ]);

        return $order->fresh('items');
    }
}
