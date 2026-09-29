<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_sign_in_with_a_bearer_token(): void
    {
        User::factory()->create(['email' => 'login@example.test', 'password' => Hash::make('SecurePass9!')]);

        $response = $this->postJson('/api/v1/auth/login', ['email' => 'login@example.test', 'password' => 'SecurePass9!']);

        $response->assertOk()
            ->assertJsonPath('data.user.email', 'login@example.test')
            ->assertJsonPath('data.token_type', 'Bearer');
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login_succeeded']);
    }

    public function test_failed_login_is_audited_without_the_password(): void
    {
        $this->postJson('/api/v1/auth/login', ['email' => 'nobody@example.test', 'password' => 'NeverLogThis9!'])
            ->assertUnauthorized()->assertJsonStructure(['message', 'code']);

        $log = AuditLog::where('action', 'auth.login_failed')->firstOrFail();
        $this->assertSame('nobody@example.test', $log->meta['email']);
        $this->assertStringNotContainsString('NeverLogThis9!', json_encode($log->meta));
    }

    public function test_invalid_login_input_is_also_audited_without_sensitive_fields(): void
    {
        $this->postJson('/api/v1/auth/login', ['email' => 'bad-email', 'password' => 'MustNeverBeStored!'])
            ->assertUnprocessable();

        $log = AuditLog::where('action', 'auth.login_failed')->firstOrFail();
        $this->assertSame('bad-email', $log->meta['email']);
        $this->assertStringNotContainsString('MustNeverBeStored!', json_encode($log->meta));
    }

    public function test_user_can_sign_in_regardless_of_legacy_verification_timestamp(): void
    {
        User::factory()->unverified()->create(['email' => 'legacy@example.test', 'password' => Hash::make('SecurePass9!')]);

        $this->postJson('/api/v1/auth/login', ['email' => 'legacy@example.test', 'password' => 'SecurePass9!'])
            ->assertOk()->assertJsonPath('data.user.email', 'legacy@example.test');
    }

    public function test_login_is_rate_limited_after_six_attempts(): void
    {
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->postJson('/api/v1/auth/login', ['email' => 'nobody@example.test', 'password' => 'WrongPass9!'])
                ->assertUnauthorized();
        }

        $this->postJson('/api/v1/auth/login', ['email' => 'nobody@example.test', 'password' => 'WrongPass9!'])
            ->assertTooManyRequests();
    }
}