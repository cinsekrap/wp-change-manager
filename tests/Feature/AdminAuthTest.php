<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    public function test_login_page_loads(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
    }

    public function test_can_login_with_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('secret123'),
            'is_active' => true,
            'is_admin' => true,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_cannot_login_when_inactive(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => bcrypt('secret123'),
            'is_active' => false,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'inactive@example.com',
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_routes_require_auth(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_admin(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'is_admin' => false,
            'mfa_enabled' => true,
            'mfa_confirmed_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['mfa_verified' => true])
            ->get('/admin');

        $response->assertStatus(403);
    }

    public function test_logout_works(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'is_admin' => true,
        ]);

        $this->actingAs($user);

        $response = $this->post('/admin/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
