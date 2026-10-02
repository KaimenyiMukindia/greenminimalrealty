<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_create_and_update_content_but_not_delete(): void
    {
        $user = User::factory()->create(['role' => UserRole::Editor]);
        $token = $user->createToken('test')->plainTextToken;
        $response = $this->withToken($token)->postJson('/api/v1/admin/services', ['title' => 'Agency', 'short_description' => 'Advice']);
        $response->assertCreated()->assertJsonPath('data.slug', 'agency');
        $id = $response->json('data.id');
        $this->withToken($token)->patchJson("/api/v1/admin/services/{$id}", ['title' => 'Updated'])->assertOk();
        $this->withToken($token)->deleteJson("/api/v1/admin/services/{$id}")->assertForbidden();
        $this->assertDatabaseHas('audit_logs', ['action' => 'content.created']);
    }
}