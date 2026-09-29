<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ResetPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_reset_token_can_be_used_once(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.test']);
        $token = Password::createToken($user);
        $payload = [
            'email' => $user->email, 'token' => $token,
            'password' => 'AnotherSecure9!', 'password_confirmation' => 'AnotherSecure9!',
        ];

        $this->postJson('/api/v1/auth/reset-password', $payload)->assertOk()->assertJsonPath('data.code', 'password_reset');
        $this->assertTrue(Hash::check('AnotherSecure9!', $user->fresh()->password));
        $this->postJson('/api/v1/auth/reset-password', $payload)->assertUnprocessable();
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.password_reset']);
    }

    public function test_an_invalid_reset_token_does_not_change_the_password(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.test']);
        $oldPassword = $user->password;

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email, 'token' => 'invalid',
            'password' => 'AnotherSecure9!', 'password_confirmation' => 'AnotherSecure9!',
        ])->assertUnprocessable();
        $this->assertSame($oldPassword, $user->fresh()->password);
    }

    public function test_reset_password_is_rate_limited_after_six_requests(): void
    {
        $user = User::factory()->create(['email' => 'rate-limit@example.test']);
        $payload = [
            'email' => $user->email,
            'token' => 'invalid',
            'password' => 'AnotherSecure9!',
            'password_confirmation' => 'AnotherSecure9!',
        ];

        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->postJson('/api/v1/auth/reset-password', $payload)->assertUnprocessable();
        }

        $this->postJson('/api/v1/auth/reset-password', $payload)->assertTooManyRequests();
    }
}