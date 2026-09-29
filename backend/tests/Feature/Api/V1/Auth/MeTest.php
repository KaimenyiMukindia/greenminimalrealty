<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_returns_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()->assertJsonPath('data.email', $user->email);
    }

    public function test_legacy_unverified_user_can_access_the_dashboard_api(): void
    {
        $user = User::factory()->unverified()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/auth/me')
            ->assertOk()->assertJsonPath('data.email', $user->email);
    }

    public function test_me_rejects_a_missing_or_invalid_token(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized()->assertJsonStructure(['message', 'code']);
        $this->withToken('not-a-valid-token')->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_me_rejects_an_idle_token(): void
    {
        $user = User::factory()->create();
        $plainToken = $user->createToken('test')->plainTextToken;
        $token = $user->tokens()->firstOrFail();
        $token->forceFill(['created_at' => now()])->save();

        $this->travel(3)->hours();
        $this->withToken($plainToken)->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
    }
}