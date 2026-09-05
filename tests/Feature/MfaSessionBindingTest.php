<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * A passed MFA challenge proves one person is who they say they are. It has to
 * stay attached to that person: a session flag that merely says "somebody here
 * passed MFA" is satisfied by anyone the session later becomes.
 */
class MfaSessionBindingTest extends TestCase
{
    private function admin(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'is_active' => true,
            'role' => 'super_admin',
            'password' => Hash::make('correct-horse-battery-staple'),
            'mfa_enabled' => true,
            'mfa_confirmed_at' => now(),
            'mfa_secret' => (new Google2FA())->generateSecretKey(),
        ], $overrides));
    }

    public function test_a_verification_does_not_survive_into_the_next_sign_in(): void
    {
        $first = $this->admin();
        $second = $this->admin();

        // Somebody clears MFA legitimately as themselves.
        $this->actingAs($first)->withSession(['mfa_verified_user_id' => $first->id]);
        $this->get(route('admin.dashboard'))->assertSuccessful();

        // They sign out, and someone else signs in on the same browser.
        $this->post(route('logout'));
        $this->post('/admin/login', [
            'email' => $second->email,
            'password' => 'correct-horse-battery-staple',
        ]);

        // The second person must pass their own challenge, not inherit the first's.
        $this->assertAuthenticatedAs($second);
        $this->get(route('admin.dashboard'))->assertRedirect(route('mfa.challenge'));
    }

    public function test_a_signed_in_user_cannot_re_authenticate_as_someone_else(): void
    {
        $attacker = $this->admin();
        $victim = $this->admin();

        $this->actingAs($attacker)->withSession(['mfa_verified_user_id' => $attacker->id]);

        $this->post('/admin/login', [
            'email' => $victim->email,
            'password' => 'correct-horse-battery-staple',
        ])->assertRedirect(route('admin.dashboard'));

        // Still the attacker; the login POST changed nobody.
        $this->assertSame($attacker->id, auth()->id());
    }

    public function test_a_flag_naming_a_different_user_is_not_accepted(): void
    {
        $user = $this->admin();
        $someoneElse = $this->admin();

        $this->actingAs($user)->withSession(['mfa_verified_user_id' => $someoneElse->id]);

        $this->get(route('admin.dashboard'))->assertRedirect(route('mfa.challenge'));
    }

    public function test_a_truthy_legacy_flag_is_not_accepted(): void
    {
        $user = $this->admin();

        // Sessions live on disk across a deploy. The old boolean must not satisfy
        // the new check just because it is truthy.
        $this->actingAs($user)->withSession(['mfa_verified' => true]);

        $this->get(route('admin.dashboard'))->assertRedirect(route('mfa.challenge'));
    }

    public function test_passing_the_challenge_admits_the_user_who_passed_it(): void
    {
        $user = $this->admin();
        $code = (new Google2FA())->getCurrentOtp($user->mfa_secret);

        $this->actingAs($user);

        $this->post(route('mfa.verify'), ['code' => $code])->assertRedirect();
        $this->get(route('admin.dashboard'))->assertSuccessful();

        // Bound to them by id, not a bare boolean.
        $this->assertSame($user->id, session('mfa_verified_user_id'));
    }

    public function test_completing_setup_admits_the_user_who_completed_it(): void
    {
        $user = $this->admin(['mfa_enabled' => false, 'mfa_confirmed_at' => null, 'mfa_secret' => null]);
        $secret = (new Google2FA())->generateSecretKey();

        $this->actingAs($user)->withSession(['mfa_setup_secret' => $secret]);

        $this->post(route('mfa.confirm'), ['code' => (new Google2FA())->getCurrentOtp($secret)])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame($user->id, session('mfa_verified_user_id'));
        $this->get(route('admin.dashboard'))->assertSuccessful();
    }

    public function test_a_normal_sign_in_still_reaches_the_challenge_then_the_dashboard(): void
    {
        $user = $this->admin();

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'correct-horse-battery-staple',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->get(route('admin.dashboard'))->assertRedirect(route('mfa.challenge'));

        $this->post(route('mfa.verify'), ['code' => (new Google2FA())->getCurrentOtp($user->mfa_secret)]);
        $this->get(route('admin.dashboard'))->assertSuccessful();
    }
}
