<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    /**
     * Create and authenticate an admin user with MFA session flag set.
     */
    protected function loginAsAdmin(array $overrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'is_active' => true,
            'role' => 'super_admin',
            'mfa_enabled' => true,
            'mfa_confirmed_at' => now(),
        ], $overrides));

        $this->actingAs($user)->withSession(['mfa_verified' => true]);

        return $user;
    }
}
