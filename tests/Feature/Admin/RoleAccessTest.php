<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_from_admin_area(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_owner_can_access_admin_dashboard(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get('/admin')->assertOk();
    }

    public function test_cashier_cannot_access_admin_area(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->get('/admin')->assertForbidden();
    }

    public function test_cashier_can_access_pos(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($cashier)->get('/pos')->assertOk();
    }

    public function test_login_redirects_cashier_to_pos(): void
    {
        $cashier = User::factory()->cashier()->create();

        $this->post('/login', [
            'email' => $cashier->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->get(route('dashboard'))->assertRedirect(route('pos.index'));
    }
}
