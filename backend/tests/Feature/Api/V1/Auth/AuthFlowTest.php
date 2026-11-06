<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Enums\UserRole;
use App\Mail\Auth\AuthLinkMail;
use App\Models\User;
use App\Services\AuthenticationService;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as GoogleUser;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_and_login_require_valid_input_and_cannot_assign_an_admin_role(): void
    {
        Mail::fake();
        $this->postJson('/api/v1/auth/register', [])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);
        $this->postJson('/api/v1/auth/login', [])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
        $this->postJson('/api/v1/auth/register', [
            'name' => 'New member', 'email' => ' MEMBER@example.test ', 'password' => 'SecurePass9!',
            'password_confirmation' => 'SecurePass9!', 'role' => 'admin',
        ])->assertCreated()->assertJsonPath('data.user.role', 'user')->assertJsonPath('data.user.email_verified_at', null);
        $this->assertTrue(Hash::check('SecurePass9!', User::where('email', 'member@example.test')->firstOrFail()->password));
        Mail::assertQueuedCount(1);
    }

    public function test_unverified_users_can_manage_verification_but_not_dashboard_data(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create(['role' => UserRole::Admin]);
        $token = $user->createToken('test')->plainTextToken;
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.email_verified_at', null);
        $this->withToken($token)->getJson('/api/v1/auth/dashboard')->assertForbidden();
        $this->withToken($token)->getJson('/api/v1/admin/properties')->assertForbidden();
        $this->withToken($token)->postJson('/api/v1/auth/email/verification-notification')->assertOk();
        Mail::assertQueued(AuthLinkMail::class, fn (AuthLinkMail $mail): bool => $mail->hasTo($user->email));
        $url = URL::temporarySignedRoute('api.v1.auth.verification.verify', now()->addMinutes(60), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->getJson($url)->assertOk()->assertJsonPath('data.code', 'email_verified');
        $this->assertNotNull($user->fresh()->email_verified_at);
        app('auth')->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/admin/properties')->assertOk();
    }

    public function test_verification_rejects_tampered_expired_and_old_email_links(): void
    {
        $user = User::factory()->unverified()->create();
        $url = URL::temporarySignedRoute('api.v1.auth.verification.verify', now()->addMinutes(60), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->getJson($url.'&modified=true')->assertForbidden();
        $user->update(['email' => 'changed@example.test']);
        $this->getJson($url)->assertForbidden();
        $this->travel(61)->minutes();
        $this->getJson($url)->assertForbidden();
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verification_resend_is_authenticated_and_rate_limited(): void
    {
        Mail::fake();
        $this->postJson('/api/v1/auth/email/verification-notification')->assertUnauthorized();
        $user = User::factory()->unverified()->create();
        $token = $user->createToken('test')->plainTextToken;
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->withToken($token)->postJson('/api/v1/auth/email/verification-notification')->assertOk();
        }
        $this->withToken($token)->postJson('/api/v1/auth/email/verification-notification')->assertTooManyRequests();
        Mail::assertQueuedCount(3);
    }

    public function test_reset_tokens_are_hashed_expire_and_revoke_all_sessions_on_success(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.test']);
        $first = $user->createToken('first')->plainTextToken;
        $second = $user->createToken('second')->plainTextToken;
        $code = app(AuthenticationService::class)->createGoogleExchange($this->googleUser($user->email), hash('sha256', str_repeat('a', 64)));
        $token = Password::createToken($user);
        $stored = DB::table('password_reset_tokens')->where('email', $user->email)->value('token');
        $this->assertNotSame($token, $stored);
        $this->assertTrue(Hash::check($token, $stored));
        $payload = ['email' => $user->email, 'token' => $token, 'password' => 'UpdatedPass9!', 'password_confirmation' => 'UpdatedPass9!'];
        $this->travel((int) config('auth.passwords.users.expire') + 1)->minutes();
        $this->postJson('/api/v1/auth/reset-password', $payload)->assertUnprocessable();
        $this->travelBack();
        $this->postJson('/api/v1/auth/reset-password', $payload)->assertOk();
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertSame(0, $user->tokens()->count());
        $this->postJson('/api/v1/auth/google/exchange', ['code' => $code, 'verifier' => str_repeat('a', 64)])->assertUnprocessable();
        $this->withToken($first)->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->withToken($second)->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->postJson('/api/v1/auth/reset-password', $payload)->assertUnprocessable();
    }

    public function test_expired_and_idle_tokens_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin]);
        $expired = $user->createToken('expired', ['*'], now()->subMinute())->plainTextToken;
        $this->withToken($expired)->getJson('/api/v1/admin/properties')->assertUnauthorized();
        $idle = $user->createToken('idle');
        $idle->accessToken->forceFill(['created_at' => now()->subHours(3)])->save();
        $this->withToken($idle->plainTextToken)->getJson('/api/v1/admin/properties')->assertUnauthorized();
    }

    public function test_google_callback_issues_only_a_browser_bound_single_use_code(): void
    {
        $verifier = str_repeat('a', 64);
        $this->mockGoogle($this->googleUser());
        $callback = $this->withSession(['google_challenge' => hash('sha256', $verifier), 'google_started_at' => now()->timestamp])
            ->get('/auth/google/callback');
        $callback->assertRedirect();
        parse_str(parse_url($callback->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertArrayHasKey('code', $query);
        $this->assertArrayNotHasKey('access_token', $query);
        $this->assertDatabaseHas('oauth_exchange_codes', ['code_hash' => hash('sha256', $query['code'])]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->postJson('/api/v1/auth/google/exchange', ['code' => $query['code'], 'verifier' => str_repeat('b', 64)])->assertUnprocessable();
        $result = $this->postJson('/api/v1/auth/google/exchange', ['code' => $query['code'], 'verifier' => $verifier])
            ->assertOk()->assertJsonPath('data.user.email', 'google@example.test')->assertJsonPath('data.user.has_password', false);
        $this->withToken($result->json('data.access_token'))->getJson('/api/v1/auth/dashboard')->assertOk();
        $this->postJson('/api/v1/auth/google/exchange', ['code' => $query['code'], 'verifier' => $verifier])->assertUnprocessable();
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_google_rejects_unverified_provider_email_and_unverified_local_account(): void
    {
        $google = $this->googleUser();
        $google->user['email_verified'] = false;
        $this->mockGoogle($google);
        $this->withSession(['google_challenge' => str_repeat('a', 64), 'google_started_at' => now()->timestamp])
            ->get('/auth/google/callback')->assertRedirect(config('app.frontend_url').'/dashboard/oauth/callback?error=google_failed');
        $this->assertDatabaseCount('users', 0);
        $this->mockGoogle($this->googleUser());
        User::factory()->unverified()->create(['email' => 'google@example.test']);
        $this->withSession(['google_challenge' => str_repeat('a', 64), 'google_started_at' => now()->timestamp])
            ->get('/auth/google/callback')->assertRedirect(config('app.frontend_url').'/dashboard/oauth/callback?error=google_failed');
        $this->assertDatabaseCount('oauth_exchange_codes', 0);
    }

    public function test_google_state_failure_and_cancellation_do_not_issue_codes(): void
    {
        $provider = \Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->once()->andThrow(new InvalidStateException);
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);
        $this->withSession(['google_challenge' => str_repeat('a', 64), 'google_started_at' => now()->timestamp])
            ->get('/auth/google/callback')->assertRedirect(config('app.frontend_url').'/dashboard/oauth/callback?error=google_failed');
        $this->get('/auth/google/callback?error=access_denied')->assertRedirect(config('app.frontend_url').'/dashboard/oauth/callback?error=google_failed');
        $this->assertDatabaseCount('oauth_exchange_codes', 0);
    }

    public function test_google_setup_missing_credentials_returns_an_explicit_error(): void
    {
        config(['services.google.client_id' => null]);
        $this->get('/auth/google?challenge='.str_repeat('a', 64))->assertRedirect(config('app.frontend_url').'/dashboard/oauth/callback?error=google_unavailable');
        $this->postJson('/api/v1/auth/google/exchange', [])->assertUnprocessable()->assertJsonValidationErrors(['code', 'verifier']);
    }

    public function test_google_links_verified_users_preserving_role_and_expired_codes_fail(): void
    {
        $user = User::factory()->create(['email' => 'google@example.test', 'role' => UserRole::Editor]);
        $verifier = str_repeat('a', 64);
        $code = app(AuthenticationService::class)->createGoogleExchange($this->googleUser(), hash('sha256', $verifier));
        $this->assertSame(UserRole::Editor, $user->fresh()->role);
        $this->assertSame('google-123', $user->fresh()->google_id);
        $this->travel(61)->seconds();
        $this->postJson('/api/v1/auth/google/exchange', ['code' => $code, 'verifier' => $verifier])->assertUnprocessable();
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_google_exchange_is_rate_limited(): void
    {
        $payload = ['code' => str_repeat('a', 64), 'verifier' => str_repeat('a', 64)];
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->postJson('/api/v1/auth/google/exchange', $payload)->assertUnprocessable();
        }
        $this->postJson('/api/v1/auth/google/exchange', $payload)->assertTooManyRequests();
    }

    public function test_email_change_requires_password_and_reverification_without_changing_role(): void
    {
        Mail::fake();
        $user = User::factory()->create(['password' => 'SecurePass9!', 'role' => UserRole::Editor, 'google_id' => 'old-google']);
        $token = $user->createToken('test')->plainTextToken;
        $body = ['name' => 'Changed', 'email' => 'changed@example.test', 'role' => 'admin'];
        $this->withToken($token)->patchJson('/api/v1/auth/me', $body)->assertUnprocessable()->assertJsonValidationErrors(['current_password']);
        $this->withToken($token)->patchJson('/api/v1/auth/me', $body + ['current_password' => 'SecurePass9!'])
            ->assertOk()->assertJsonPath('data.email_verified_at', null)->assertJsonPath('data.role', 'editor');
        $this->assertNull($user->fresh()->google_id);
        $this->withToken($token)->getJson('/api/v1/admin/properties')->assertForbidden();
        Mail::assertQueuedCount(1);
    }

    public function test_deletion_requires_confirmation_revokes_tokens_and_anonymizes_account(): void
    {
        $user = User::factory()->create(['password' => 'SecurePass9!']);
        $first = $user->createToken('first')->plainTextToken;
        $second = $user->createToken('second')->plainTextToken;
        Password::createToken($user);
        $this->withToken($first)->deleteJson('/api/v1/auth/me', ['confirmed' => true, 'current_password' => 'wrong'])->assertUnprocessable();
        $this->assertNotSoftDeleted($user);
        $this->withToken($first)->deleteJson('/api/v1/auth/me', ['confirmed' => true, 'current_password' => 'SecurePass9!'])
            ->assertOk()->assertJsonPath('data.code', 'account_deleted');
        $this->assertSoftDeleted($user);
        $this->assertSame(0, $user->tokens()->count());
        $deleted = User::withTrashed()->findOrFail($user->id);
        $this->assertSame('Deleted account', $deleted->name);
        $this->assertNotSame($user->email, $deleted->email);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->withToken($second)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_google_only_accounts_can_delete_with_explicit_confirmation(): void
    {
        $user = User::factory()->create(['password_set' => false]);
        $token = $user->createToken('test')->plainTextToken;
        $this->withToken($token)->deleteJson('/api/v1/auth/me', [])->assertUnprocessable();
        $this->withToken($token)->deleteJson('/api/v1/auth/me', ['confirmed' => true])->assertOk();
        $this->assertSoftDeleted($user);
    }

    public function test_auth_emails_render_working_frontend_links(): void
    {
        $mail = new AuthLinkMail('Reset your password', 'https://www.example.test/dashboard/reset-password?token=sample', 'Reset password', 60);
        $mail->assertSeeInHtml('https://www.example.test/dashboard/reset-password?token=sample');
        $mail->assertSeeInText('60 minutes');
    }

    public function test_google_callback_uses_real_state_validation_and_google_userinfo_mapping(): void
    {
        $handler = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['access_token' => 'test-google-token', 'expires_in' => 3600])),
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'sub' => 'google-123', 'email' => 'google@example.test', 'email_verified' => true, 'name' => 'Google member',
            ])),
        ]);
        config([
            'services.google.client_id' => 'test-client',
            'services.google.client_secret' => 'test-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
            'services.google.guzzle.handler' => HandlerStack::create($handler),
        ]);
        $this->withSession(['state' => 'real-state', 'google_challenge' => str_repeat('a', 64), 'google_started_at' => now()->timestamp])
            ->get('/auth/google/callback?state=real-state&code=test-code')->assertRedirect();
        $this->assertDatabaseHas('users', ['google_id' => 'google-123', 'email' => 'google@example.test']);
        $this->assertDatabaseCount('oauth_exchange_codes', 1);
        $this->assertSame(0, $handler->count());
    }

    public function test_google_real_provider_rejects_mismatched_oauth_state_without_http_calls(): void
    {
        $handler = new MockHandler([]);
        config([
            'services.google.client_id' => 'test-client', 'services.google.client_secret' => 'test-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
            'services.google.guzzle.handler' => HandlerStack::create($handler),
        ]);
        $this->withSession(['state' => 'real-state', 'google_challenge' => str_repeat('a', 64), 'google_started_at' => now()->timestamp])
            ->get('/auth/google/callback?state=wrong-state&code=test-code')
            ->assertRedirect(config('app.frontend_url').'/dashboard/oauth/callback?error=google_failed');
        $this->assertDatabaseCount('oauth_exchange_codes', 0);
    }

    public function test_google_registration_and_returning_sign_in_complete_the_full_round_trip(): void
    {
        Mail::fake();
        $handler = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['access_token' => 'test-google-token', 'expires_in' => 3600])),
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'sub' => 'google-123', 'email' => 'google@example.test', 'email_verified' => true, 'name' => 'Google member',
            ])),
            new Response(200, ['Content-Type' => 'application/json'], json_encode(['access_token' => 'returning-google-token', 'expires_in' => 3600])),
            new Response(200, ['Content-Type' => 'application/json'], json_encode([
                'sub' => 'google-123', 'email' => 'google@example.test', 'email_verified' => true, 'name' => 'Google member',
            ])),
        ]);
        config([
            'services.google.client_id' => 'test-client',
            'services.google.client_secret' => 'test-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
            'services.google.guzzle.handler' => HandlerStack::create($handler),
        ]);

        $userId = null;
        foreach (['new-user', 'returning-user'] as $attempt) {
            Socialite::forgetDrivers();
            $verifier = hash('sha256', $attempt);
            $redirect = $this->get('/auth/google?challenge='.hash('sha256', $verifier))->assertRedirect();
            $authorizationUrl = $redirect->headers->get('Location');
            $this->assertSame('accounts.google.com', parse_url($authorizationUrl, PHP_URL_HOST));
            parse_str(parse_url($authorizationUrl, PHP_URL_QUERY), $authorization);
            $this->assertSame('test-client', $authorization['client_id']);
            $this->assertSame('http://localhost/auth/google/callback', $authorization['redirect_uri']);
            $this->assertStringContainsString('email', $authorization['scope']);
            $this->assertNotEmpty($authorization['state']);
            $redirect->assertSessionHas('state', $authorization['state']);
            $redirect->assertSessionHas('google_challenge', hash('sha256', $verifier));

            Socialite::forgetDrivers();
            $callback = $this->get('/auth/google/callback?'.http_build_query([
                'state' => $authorization['state'], 'code' => $attempt.'-provider-code',
            ]))->assertRedirect()->assertSessionMissing('state')->assertSessionMissing('google_challenge');
            $location = $callback->headers->get('Location');
            $this->assertStringStartsWith(config('app.frontend_url').'/dashboard/oauth/callback?', $location);
            parse_str(parse_url($location, PHP_URL_QUERY), $query);
            $this->assertSame(['code'], array_keys($query));

            $exchange = $this->postJson('/api/v1/auth/google/exchange', ['code' => $query['code'], 'verifier' => $verifier])
                ->assertOk()->assertJsonPath('data.user.has_password', false)->assertJsonPath('data.user.role', 'user');
            $this->assertNotNull($exchange->json('data.user.email_verified_at'));
            if ($userId === null) {
                $userId = $exchange->json('data.user.id');
            }
            $exchange->assertJsonPath('data.user.id', $userId);
            $token = $exchange->json('data.access_token');
            $this->assertDatabaseCount('users', 1);
            $this->assertDatabaseCount('oauth_exchange_codes', 0);
            $this->withToken($token)->getJson('/api/v1/auth/dashboard')
                ->assertOk()->assertJsonPath('data.dashboard_path', '/dashboard/account');
            app('auth')->forgetGuards();
            $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.id', $userId);
            $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
            app('auth')->forgetGuards();
            $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
        }
        $this->assertSame(0, $handler->count());
        $this->assertDatabaseCount('personal_access_tokens', 0);
        Mail::assertNothingOutgoing();
    }

    public function test_google_provider_error_returns_to_nuxt_without_creating_an_account(): void
    {
        $handler = new MockHandler([new Response(400, ['Content-Type' => 'application/json'], json_encode(['error' => 'invalid_grant']))]);
        config([
            'services.google.client_id' => 'test-client',
            'services.google.client_secret' => 'test-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
            'services.google.guzzle.handler' => HandlerStack::create($handler),
        ]);
        $this->withSession(['state' => 'valid-state', 'google_challenge' => str_repeat('a', 64), 'google_started_at' => now()->timestamp])
            ->get('/auth/google/callback?state=valid-state&code=rejected-code')
            ->assertRedirect(config('app.frontend_url').'/dashboard/oauth/callback?error=google_failed')
            ->assertSessionMissing('state')->assertSessionMissing('google_challenge');
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('oauth_exchange_codes', 0);
        $this->assertSame(0, $handler->count());
    }

    public function test_the_last_administrator_cannot_delete_their_account(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin, 'password' => 'SecurePass9!']);
        $this->withToken($user->createToken('test')->plainTextToken)
            ->deleteJson('/api/v1/auth/me', ['confirmed' => true, 'current_password' => 'SecurePass9!'])->assertUnprocessable();
        $this->assertNotSoftDeleted($user);
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_logout_all_also_revokes_pending_google_exchanges(): void
    {
        $user = User::factory()->create(['email' => 'google@example.test']);
        $code = app(AuthenticationService::class)->createGoogleExchange($this->googleUser(), hash('sha256', str_repeat('a', 64)));
        $token = $user->createToken('test')->plainTextToken;
        $this->withToken($token)->postJson('/api/v1/auth/logout-all')->assertOk();
        $this->postJson('/api/v1/auth/google/exchange', ['code' => $code, 'verifier' => str_repeat('a', 64)])->assertUnprocessable();
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_laravel_selects_the_dashboard_destination_without_granting_admin_access(): void
    {
        $member = User::factory()->create(['role' => UserRole::User]);
        $this->withToken($member->createToken('test')->plainTextToken)->getJson('/api/v1/auth/dashboard')
            ->assertOk()->assertJsonPath('data.dashboard_path', '/dashboard/account');
        app('auth')->forgetGuards();
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $this->withToken($editor->createToken('test')->plainTextToken)->getJson('/api/v1/auth/dashboard')
            ->assertOk()->assertJsonPath('data.dashboard_path', '/dashboard/properties');
    }

    private function googleUser(string $email = 'google@example.test'): GoogleUser
    {
        return (new GoogleUser)->setRaw(['email_verified' => true])->map([
            'id' => 'google-123', 'email' => $email, 'name' => 'Google member',
        ]);
    }

    private function mockGoogle(GoogleUser $user): void
    {
        $provider = \Mockery::mock(GoogleProvider::class);
        $provider->shouldReceive('user')->once()->andReturn($user);
        Socialite::shouldReceive('driver')->with('google')->once()->andReturn($provider);
    }
}
