<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Models\Media;
use App\Models\Menu;
use App\Models\Property;
use App\Models\Stat;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardWriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_metadata_and_html_blocks_are_saved_and_returned_by_public_api(): void
    {
        $token = $this->tokenFor(UserRole::Admin);
        $response = $this->withToken($token)->postJson('/api/v1/admin/page-meta', [
            'title' => 'About',
            'route' => '/client-controlled-route',
            'meta_title' => 'About our company',
            'meta_description' => 'Company overview',
            'blocks' => [
                ['key' => 'intro', 'type' => 'rich_text', 'content' => '<p onclick="run()">Editable <strong>HTML</strong><script>alert(1)</script><a href="javascript:alert(1)">safe label</a></p>', 'order' => 0],
            ],
        ]);

        $response->assertCreated()->assertJsonPath('data.route', '/about')->assertJsonPath('data.meta_title', 'About our company');
        $this->assertDatabaseHas('content_blocks', ['key' => 'intro', 'content' => '<p>Editable <strong>HTML</strong><a>safe label</a></p>']);
        $this->getJson('/api/v1/public/meta?route=%2Fabout')
            ->assertOk()
            ->assertJsonPath('data.title', 'About our company')
            ->assertJsonPath('data.meta_description', 'Company overview')
            ->assertJsonPath('data.blocks.0.content', '<p>Editable <strong>HTML</strong><a>safe label</a></p>');
    }

    public function test_property_gallery_tags_and_fields_save_in_one_transaction_and_slug_is_server_generated(): void
    {
        $token = $this->tokenFor(UserRole::Editor);
        $first = Media::create(['path' => 'uploads/first.jpg', 'url' => '/storage/first.jpg', 'mime' => 'image/jpeg', 'size' => 12]);
        $second = Media::create(['path' => 'uploads/second.jpg', 'url' => '/storage/second.jpg', 'mime' => 'image/jpeg', 'size' => 24]);
        $created = $this->withToken($token)->postJson('/api/v1/admin/properties', [
            'title' => 'Green Villa', 'slug' => 'client-controlled', 'location' => 'Watamu', 'price_label' => 'From $100',
            'price_value' => 100, 'beds' => 3, 'baths' => 2, 'is_published' => true,
            'tags' => ['Beachfront', 'Pool'], 'media_ids' => [$first->id, $second->id],
        ])->assertCreated()->assertJsonPath('data.slug', 'green-villa');

        $propertyId = $created->json('data.id');
        $this->assertDatabaseHas('properties', ['id' => $propertyId, 'slug' => 'green-villa']);
        $this->assertDatabaseHas('property_tags', ['property_id' => $propertyId, 'label' => 'Beachfront']);
        $this->assertDatabaseHas('property_images', ['property_id' => $propertyId, 'media_id' => $second->id]);

        $this->withToken($token)->patchJson("/api/v1/admin/properties/{$propertyId}", [
            'title' => 'Green Villa Updated', 'tags' => ['Garden'], 'media_ids' => [$second->id], 'is_published' => false,
        ])->assertOk()->assertJsonPath('data.title', 'Green Villa Updated');

        $this->assertDatabaseHas('properties', ['id' => $propertyId, 'is_published' => false]);
        $this->assertDatabaseHas('property_tags', ['property_id' => $propertyId, 'label' => 'Garden', 'deleted_at' => null]);
        $this->assertDatabaseHas('property_images', ['property_id' => $propertyId, 'media_id' => $second->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('media', ['id' => $first->id]);
    }

    public function test_menu_items_are_editable_and_primary_menu_updates_public_settings(): void
    {
        $token = $this->tokenFor(UserRole::Admin);
        $created = $this->withToken($token)->postJson('/api/v1/admin/menus', [
            'name' => 'Primary Navigation', 'location' => 'primary',
            'items' => [
                ['label' => 'Home', 'url' => '/', 'order' => 0],
                ['label' => 'About', 'url' => '/about', 'order' => 1],
            ],
        ])->assertCreated()->assertJsonPath('data.items.0.label', 'Home');

        $menuId = $created->json('data.id');
        $items = $created->json('data.items');
        $this->assertDatabaseHas('site_settings', ['id' => 1]);
        $settings = $this->getJson('/api/v1/public/settings');
        $this->assertSame('/about', $settings->json('data.nav_items.1.to'), json_encode($settings->json()));

        $this->withToken($token)->postJson('/api/v1/admin/menus/reorder', [
            'menu_id' => $menuId, 'ids' => [$items[1]['id'], $items[0]['id']],
        ])->assertOk();

        $this->assertSame('/about', $this->getJson('/api/v1/public/settings')->json('data.nav_items.0.to'));
        $this->withToken($token)->patchJson("/api/v1/admin/menus/{$menuId}", [
            'name' => 'Primary Navigation', 'location' => 'primary',
            'items' => [['id' => $items[1]['id'], 'label' => 'Our Story', 'url' => '/about-us']],
        ])->assertOk();
        $this->assertSame('/about-us', $this->getJson('/api/v1/public/settings')->json('data.nav_items.0.to'));
    }

    public function test_menu_parent_children_created_in_the_same_save_are_linked_and_published(): void
    {
        $token = $this->tokenFor(UserRole::Admin);
        $response = $this->withToken($token)->postJson('/api/v1/admin/menus', [
            'name' => 'Primary Navigation', 'location' => 'primary',
            'items' => [
                ['client_key' => 'root-a', 'label' => 'Explore', 'url' => '/properties', 'order' => 0],
                ['client_key' => 'child-a', 'parent_client_key' => 'root-a', 'label' => 'Coastal homes', 'url' => '/properties?coast=1', 'order' => 0],
            ],
        ])->assertCreated()->assertJsonPath('data.items.0.children.0.label', 'Coastal homes');

        $root = \App\Models\MenuItem::where('label', 'Explore')->firstOrFail();
        $child = \App\Models\MenuItem::where('label', 'Coastal homes')->firstOrFail();
        $this->assertSame($root->id, $child->parent_id);
    }

    public function test_airbnb_crud_and_list_filters_are_applied_on_the_server(): void
    {
        $token = $this->tokenFor(UserRole::Editor);
        $response = $this->withToken($token)->postJson('/api/v1/admin/airbnb-listings', [
            'title' => 'Coastal Stay', 'occupancy_stats' => [['label' => 'Occupancy', 'value' => '82%']],
            'features' => ['Ocean view', 'Solar power'], 'published' => true,
        ])->assertCreated()->assertJsonPath('data.slug', 'coastal-stay');
        $this->assertDatabaseHas('airbnb_listings', ['id' => $response->json('data.id'), 'slug' => 'coastal-stay']);

        Stat::create(['section' => 'airbnb', 'value' => '82%', 'label' => 'Occupancy', 'order' => 2]);
        Stat::create(['section' => 'hero', 'value' => '3', 'label' => 'Bedrooms', 'order' => 1]);
        $this->withToken($token)->getJson('/api/v1/admin/stats?section=airbnb&search=Occupancy&order=desc')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.label', 'Occupancy');

        $this->withToken($token)->patchJson('/api/v1/admin/airbnb-listings/' . $response->json('data.id'), ['published' => false])->assertOk();
        $this->getJson('/api/v1/public/airbnb-listings')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_regular_user_cannot_read_admin_content_and_editor_cannot_delete(): void
    {
        $userToken = $this->tokenFor(UserRole::User);
        $this->withToken($userToken)->getJson('/api/v1/admin/properties')->assertForbidden();

        $editorToken = $this->tokenFor(UserRole::Editor);
        $property = Property::create(['title' => 'Editor Test', 'slug' => 'editor-test', 'location' => 'Coast', 'price_label' => 'Ask']);
        $this->withToken($editorToken)->deleteJson("/api/v1/admin/properties/{$property->id}")->assertForbidden();
    }

    public function test_account_profile_and_password_can_be_changed_without_exposing_the_token(): void
    {
        $user = User::factory()->create(['role' => UserRole::Editor, 'password' => 'password']);
        $token = $user->createToken('account-test')->plainTextToken;

        $this->withToken($token)->patchJson('/api/v1/auth/me', ['name' => 'Updated Person', 'email' => 'updated@example.test'])
            ->assertOk()->assertJsonPath('data.name', 'Updated Person')->assertJsonMissingPath('data.access_token');
        $this->withToken($token)->putJson('/api/v1/auth/me/password', [
            'current_password' => 'password',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertOk();

        $this->assertTrue(Hash::check('SecurePass123!', $user->fresh()->password));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_admin_user_list_filters_roles_and_protects_the_last_administrator(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $editor = User::factory()->create(['role' => UserRole::Editor, 'name' => 'Filtered Editor']);
        $token = $admin->createToken('users-test')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/admin/users?role=editor&search=Filtered')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $editor->id);
        $this->withToken($token)->patchJson('/api/v1/admin/users/' . $admin->id, ['role' => 'editor'])->assertUnprocessable();
        $this->withToken($token)->deleteJson('/api/v1/admin/users/' . $admin->id)->assertUnprocessable();

        $ordinaryUser = User::factory()->create(['role' => UserRole::User]);
        $this->actingAs($ordinaryUser, 'sanctum')->getJson('/api/v1/admin/users')->assertForbidden();
    }

    private function tokenFor(UserRole $role): string
    {
        return User::factory()->create(['role' => $role])->createToken('admin-test')->plainTextToken;
    }
}