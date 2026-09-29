<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_without_email_delivery(): void
    {
        Mail::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Casey Green',
            'email' => 'casey@example.test',
            'password' => 'SecurePass9!',
            'password_confirmation' => 'SecurePass9!',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.email', 'casey@example.test')
            ->assertJsonPath('data.user.role', 'user')
            ->assertJsonMissingPath('data.access_token');
        $this->assertDatabaseHas('users', ['email' => 'casey@example.test']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.registered']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'casey@example.test',
            'password' => 'SecurePass9!',
        ])->assertOk()->assertJsonPath('data.user.email', 'casey@example.test');
        Mail::assertNothingOutgoing();
    }

    public function test_registration_validates_password_and_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@example.test']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Casey', 'email' => 'taken@example.test', 'password' => 'weak', 'password_confirmation' => 'weak',
        ])->assertUnprocessable()->assertJsonStructure(['message', 'errors', 'code']);
    }

    public function test_registration_is_rate_limited(): void
    {
        $payload = ['name' => 'Casey', 'password' => 'SecurePass9!', 'password_confirmation' => 'SecurePass9!'];

        for ($index = 0; $index < 3; $index++) {
            $this->postJson('/api/v1/auth/register', $payload + ['email' => "casey{$index}@example.test"])->assertCreated();
        }

        $this->postJson('/api/v1/auth/register', $payload + ['email' => 'over-limit@example.test'])->assertTooManyRequests();
    }
}