<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_is_explicitly_unavailable_without_mail_delivery(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'reset@example.test']);

        $existing = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'reset@example.test']);
        $unknown = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'missing@example.test']);

        $existing->assertServiceUnavailable()->assertJsonPath('data.code', 'password_reset_unavailable');
        $unknown->assertServiceUnavailable()->assertJsonPath('data.code', 'password_reset_unavailable');
        $this->assertSame($existing->getContent(), $unknown->getContent());
        Mail::assertNothingOutgoing();
    }

    public function test_forgot_password_validates_email(): void
    {
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'not-an-email'])->assertUnprocessable();
    }

    public function test_forgot_password_is_rate_limited_after_three_requests(): void
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->postJson('/api/v1/auth/forgot-password', ['email' => 'missing@example.test'])->assertServiceUnavailable();
        }

        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'missing@example.test'])->assertTooManyRequests();
    }
}