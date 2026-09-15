<?php

namespace Tests\Feature;

use App\Enums\CompanyUserRole;
use App\Enums\StaffRole;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_a_customer_signs_in_and_lands_on_their_dashboard(): void
    {
        $user = $this->customer();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('account.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_staff_member_signing_in_at_the_front_lands_in_the_back_office(): void
    {
        $staff = $this->staff();

        $this->post(route('login'), ['email' => $staff->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_a_customer_cannot_use_the_back_office_door(): void
    {
        $user = $this->customer();

        $this->post(route('admin.login'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_wrong_password_gives_nothing_away(): void
    {
        $user = $this->customer();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
        $knownMessage = session('errors')->first('email');

        $this->flushSession();

        $this->post(route('login'), ['email' => 'nobody@example.test', 'password' => 'wrong'])
            ->assertSessionHasErrors('email');
        $unknownMessage = session('errors')->first('email');

        // The same message either way, so the form cannot be used to find out
        // who holds an account here.
        $this->assertSame($knownMessage, $unknownMessage);
    }

    public function test_a_deactivated_login_is_refused(): void
    {
        $user = $this->customer();
        $user->update(['is_active' => false]);

        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_customer_is_redirected_away_from_the_back_office(): void
    {
        $this->actingAs($this->customer())
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('account.dashboard'));
    }

    public function test_the_account_area_needs_a_login(): void
    {
        $this->get(route('account.dashboard'))->assertRedirect(route('login'));
    }

    public function test_the_back_office_needs_a_staff_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_registration_creates_a_pending_account_with_an_admin_login(): void
    {
        $response = $this->post(route('register'), [
            'company_name' => 'Test Promotions LLC',
            'business_type' => 'Retailer',
            'billing_street' => '1 Test Way',
            'billing_city' => 'Los Angeles',
            'billing_state' => 'CA',
            'billing_postcode' => '90021',
            'name' => 'Sam Buyer',
            'phone' => '555-0100',
            'email' => 'sam@testpromo.test',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
            'terms' => '1',
        ]);

        $response->assertRedirect(route('account.dashboard'));

        $user = User::where('email', 'sam@testpromo.test')->firstOrFail();

        $this->assertTrue($user->hasRole(CompanyUserRole::Admin->value));
        $this->assertSame('pending_approval', $user->company->status->value);

        // The whole point: they are in, and they still cannot see a price.
        $this->assertFalse($user->canSeePricing());
    }

    public function test_signing_out_returns_to_the_right_place(): void
    {
        $this->actingAs($this->customer())->post(route('logout'))->assertRedirect(route('home'));

        $this->actingAs($this->staff())->post(route('logout'))->assertRedirect(route('admin.login'));
    }

    private function customer(): User
    {
        $user = User::factory()->create([
            'company_id' => Company::factory()->create()->id,
            'password' => 'password',
        ]);

        $user->assignRole(CompanyUserRole::Buyer->value);

        return $user;
    }

    private function staff(): User
    {
        $user = User::factory()->create(['company_id' => null, 'password' => 'password']);
        $user->assignRole(StaffRole::Owner->value);

        return $user;
    }
}
