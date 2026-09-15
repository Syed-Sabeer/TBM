<?php

namespace Tests\Feature;

use App\Enums\CompanyUserRole;
use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "One company, different logins with different users, but they all should
 * have the same order history. Any contact can place the order."
 *
 * So an order belongs to the account, not to the person — and the boundary
 * that actually matters is between two different companies.
 */
class SharedOrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private User $alice;

    private User $bob;

    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->company = Company::factory()->create();

        $this->alice = $this->loginOn($this->company, CompanyUserRole::Admin);
        $this->bob = $this->loginOn($this->company, CompanyUserRole::Buyer);
        $this->viewer = $this->loginOn($this->company, CompanyUserRole::Viewer);
    }

    public function test_a_colleague_sees_an_order_someone_else_placed(): void
    {
        $order = $this->orderPlacedBy($this->alice);

        $this->actingAs($this->bob)
            ->get(route('account.orders.show', $order))
            ->assertOk()
            ->assertSee($order->reference);
    }

    public function test_the_order_list_shows_the_whole_account(): void
    {
        $this->orderPlacedBy($this->alice, 'TBM-2026-1111');
        $this->orderPlacedBy($this->bob, 'TBM-2026-2222');

        $this->actingAs($this->viewer)
            ->get(route('account.orders.index'))
            ->assertSee('TBM-2026-1111')
            ->assertSee('TBM-2026-2222');
    }

    public function test_a_view_only_login_still_sees_the_history(): void
    {
        $order = $this->orderPlacedBy($this->alice);

        $this->actingAs($this->viewer)
            ->get(route('account.orders.show', $order))
            ->assertOk();
    }

    public function test_a_view_only_login_cannot_place_an_order(): void
    {
        $this->assertFalse($this->viewer->canPlaceOrders());
        $this->assertTrue($this->bob->canPlaceOrders());

        $this->actingAs($this->viewer)
            ->get(route('checkout.show'))
            ->assertForbidden();
    }

    public function test_another_company_cannot_see_the_order(): void
    {
        $order = $this->orderPlacedBy($this->alice);
        $stranger = $this->loginOn(Company::factory()->create(), CompanyUserRole::Admin);

        $this->actingAs($stranger)
            ->get(route('account.orders.show', $order))
            ->assertForbidden();
    }

    public function test_another_company_cannot_download_the_invoice(): void
    {
        $order = $this->orderPlacedBy($this->alice);
        $stranger = $this->loginOn(Company::factory()->create(), CompanyUserRole::Admin);

        $this->actingAs($stranger)
            ->get(route('account.orders.invoice', $order))
            ->assertForbidden();
    }

    private function loginOn(Company $company, CompanyUserRole $role): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole($role->value);

        return $user;
    }

    private function orderPlacedBy(User $user, string $reference = 'TBM-2026-0001'): Order
    {
        return Order::create([
            'reference' => $reference,
            'company_id' => $user->company_id,
            'placed_by_id' => $user->id,
            'status' => OrderStatus::Confirmed,
            'merchandise_total' => 1200,
            'grand_total' => 1200,
            'total_pieces' => 500,
            'placed_at' => now()->subDays(3),
            'ship_to' => ['street' => '1 Test Way', 'city' => 'Los Angeles', 'state' => 'CA', 'postcode' => '90021'],
        ]);
    }
}
