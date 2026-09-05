<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Microsoft sign-in is not switched on yet, which is the only reason these are
 * cheap. Linking an account removes its password sign-in, so who may link, and
 * how anyone gets back, has to be settled before the first account is linked.
 */
class SsoHardeningTest extends TestCase
{
    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'is_active' => true,
            'role' => 'super_admin',
            'password' => Hash::make('correct-horse-battery-staple'),
            'mfa_enabled' => true,
            'mfa_confirmed_at' => now(),
        ], $overrides));
    }

    public function test_a_linked_account_cannot_sign_in_with_its_password(): void
    {
        $user = $this->user(['provider' => 'microsoft', 'provider_id' => 'abc123']);

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'correct-horse-battery-staple',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_wrong_password_looks_the_same_whether_the_account_is_linked_or_not(): void
    {
        $linked = $this->user(['provider' => 'microsoft', 'provider_id' => 'abc123']);
        $local = $this->user();

        $errors = [];
        foreach ([$linked, $local] as $user) {
            $this->post('/admin/login', ['email' => $user->email, 'password' => 'wrong'])
                ->assertSessionHasErrors('email');
            $errors[] = session('errors')->first('email');
            $this->flushSession();
        }

        // Otherwise the sign-in form says which addresses have accounts.
        $this->assertSame($errors[0], $errors[1]);
    }

    public function test_an_unlinked_account_still_signs_in_normally(): void
    {
        $user = $this->user();

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'correct-horse-battery-staple',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_the_mfa_skip_follows_the_session_not_the_account(): void
    {
        $user = $this->user(['provider' => 'microsoft', 'provider_id' => 'abc123']);

        // A session that did not sign in through Microsoft gets no free pass,
        // whatever the account record says.
        $this->actingAs($user);
        $this->get(route('admin.dashboard'))->assertRedirect(route('mfa.challenge'));

        $this->actingAs($user)->withSession(['auth_via' => 'microsoft']);
        $this->get(route('admin.dashboard'))->assertSuccessful();
    }

    public function test_a_super_admin_can_return_an_account_to_password_sign_in(): void
    {
        $stranded = $this->user(['provider' => 'microsoft', 'provider_id' => 'abc123']);
        $this->loginAsAdmin();

        $this->post(route('admin.users.unlink-sso', $stranded))->assertRedirect();

        // Without this, an unreachable provider locks people out for good —
        // there is no shell on this host to fix it from.
        $this->assertNull($stranded->fresh()->provider);
        $this->assertNull($stranded->fresh()->provider_id);
    }

    public function test_unlinking_is_offered_on_a_linked_account(): void
    {
        $linked = $this->user(['provider' => 'microsoft', 'provider_id' => 'abc123']);
        $this->loginAsAdmin();

        $this->get(route('admin.users.edit', $linked))
            ->assertSuccessful()
            ->assertSee(route('admin.users.unlink-sso', $linked), false);
    }

    public function test_the_break_glass_scope_finds_only_usable_accounts(): void
    {
        $this->user(['provider' => 'microsoft', 'provider_id' => 'a']);   // linked
        $this->user(['is_active' => false]);                              // deactivated
        $this->user(['role' => 'editor']);                                // cannot fix SSO settings
        $keeper = $this->user();

        $this->assertSame([$keeper->id], User::breakGlass()->pluck('id')->all());
    }

    public function test_sso_cannot_be_enabled_without_naming_a_tenant(): void
    {
        $this->loginAsAdmin();

        // A blank tenant falls back to one that accepts every Microsoft account
        // there is, including personal ones.
        $this->put(route('admin.settings.entra.update'), [
            'entra_enabled' => '1',
            'entra_client_id' => 'client-id',
            'entra_tenant_id' => '',
        ])->assertSessionHasErrors('entra_tenant_id');
    }

    public function test_the_link_offer_only_appears_when_sso_is_switched_on(): void
    {
        $this->loginAsAdmin();

        $this->get(route('admin.password.edit'))->assertDontSee('Link Microsoft sign-in');

        Setting::set('entra_enabled', '1');
        $this->get(route('admin.password.edit'))->assertSee('Link Microsoft sign-in');
    }
}
