<?php

namespace Tests\Feature\Api\V1;

use App\Models\Property;
use App\Models\PropertyTag;
use App\Models\Service;
use App\Models\ServiceItem;
use App\Models\Value;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PublicContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_public_services_are_published_paginated_and_nested(): void
    {
        $service = Service::create(['slug' => 'agency', 'title' => 'Agency', 'short_description' => 'Advice', 'order' => 1, 'published' => true]);
        ServiceItem::create(['service_id' => $service->id, 'text' => 'Sales', 'order' => 1]);
        Service::create(['slug' => 'hidden', 'title' => 'Hidden', 'short_description' => 'No', 'published' => false]);

        $this->getJson('/api/v1/public/services?per_page=1')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'agency')
            ->assertJsonPath('data.0.items.0.text', 'Sales')
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonCount(1, 'data');
    }

    public function test_unpublished_content_is_hidden_from_public_reads(): void
    {
        Value::create(['title' => 'Visible', 'description' => 'Yes', 'published' => true]);
        Value::create(['title' => 'Hidden', 'description' => 'No', 'published' => false]);

        $this->getJson('/api/v1/public/values')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Visible');
    }

    public function test_properties_support_featured_filter_and_contact_is_stored(): void
    {
        Property::create(['slug' => 'featured', 'title' => 'Featured', 'location' => 'Mombasa', 'price_label' => 'KSh 1', 'is_featured' => true, 'is_published' => true]);
        Property::create(['slug' => 'other', 'title' => 'Other', 'location' => 'Mombasa', 'price_label' => 'KSh 2', 'is_featured' => false, 'is_published' => true]);

        $this->getJson('/api/v1/public/properties?featured=1')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'featured');
        $this->postJson('/api/v1/public/contact', ['name' => 'Casey', 'email' => 'casey@example.test', 'message' => 'Hello'])->assertCreated();
        $this->assertDatabaseHas('contact_submissions', ['email' => 'casey@example.test']);
    }

    public function test_public_cache_is_flushed_when_content_changes(): void
    {
        $value = Value::create(['title' => 'Before', 'description' => 'Original', 'published' => true]);
        $this->getJson('/api/v1/public/values')->assertJsonPath('data.0.title', 'Before');
        $value->update(['title' => 'After']);
        $this->getJson('/api/v1/public/values')->assertJsonPath('data.0.title', 'After');
    }

    public function test_property_search_combines_text_numeric_and_tag_filters_and_returns_tags(): void
    {
        $matching = Property::create([
            'slug' => 'pool-villa', 'title' => 'Coastal Villa', 'location' => 'Watamu Coast',
            'description' => 'Quiet family home near the ocean', 'price_label' => 'KSh 45M', 'price_value' => 45000000,
            'beds' => 4, 'baths' => 3, 'is_published' => true,
        ]);
        PropertyTag::create(['property_id' => $matching->id, 'label' => 'Swimming pool', 'order' => 0]);
        PropertyTag::create(['property_id' => $matching->id, 'label' => 'Solar hot water', 'order' => 1]);

        $other = Property::create([
            'slug' => 'city-apartment', 'title' => 'City Apartment', 'location' => 'Nairobi',
            'description' => 'Modern apartment', 'price_label' => 'KSh 20M', 'price_value' => 20000000,
            'beds' => 2, 'baths' => 2, 'is_published' => true,
        ]);
        PropertyTag::create(['property_id' => $other->id, 'label' => 'Rooftop pool', 'order' => 0]);

        $this->getJson('/api/v1/public/properties?q=watamu%204&tags%5B%5D=Swimming%20pool')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Coastal Villa')
            ->assertJsonPath('data.0.tags.0.label', 'Swimming pool')
            ->assertJsonPath('meta.current_page', 1);

        $this->getJson('/api/v1/public/properties?q=3')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.baths', 3);
        $this->getJson('/api/v1/public/properties?q=family%20home')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'pool-villa');
        $this->getJson('/api/v1/public/properties?tags%5B%5D=Swimming%20pool&tags%5B%5D=Solar%20hot%20water')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.slug', 'pool-villa');
    }

    public function test_property_suggestions_are_unique_db_backed_published_and_limited(): void
    {
        for ($index = 1; $index <= 12; $index++) {
            $property = Property::create([
                'slug' => "pool-home-{$index}", 'title' => "Pool Home {$index}", 'location' => "Pool District {$index}",
                'price_label' => 'Ask', 'is_published' => true,
            ]);
            PropertyTag::create(['property_id' => $property->id, 'label' => "Pool feature {$index}", 'order' => 0]);
        }
        $unpublished = Property::create(['slug' => 'hidden-pool', 'title' => 'Hidden Pool House', 'location' => 'Pool Hidden', 'price_label' => 'Ask', 'is_published' => false]);
        PropertyTag::create(['property_id' => $unpublished->id, 'label' => 'Pool secret', 'order' => 0]);

        $response = $this->getJson('/api/v1/public/properties/suggestions?q=pool')
            ->assertOk()
            ->assertJsonCount(10, 'data.tags')
            ->assertJsonCount(10, 'data.locations')
            ->assertJsonCount(10, 'data.titles');
        $this->assertNotContains('Pool secret', $response->json('data.tags'));
        $this->assertSame($response->json('data.tags'), array_values(array_unique($response->json('data.tags'))));
    }

    public function test_property_suggestion_cache_is_invalidated_when_property_content_changes(): void
    {
        $property = Property::create(['slug' => 'garden-home', 'title' => 'Garden Home', 'location' => 'Watamu', 'price_label' => 'Ask', 'is_published' => true]);
        PropertyTag::create(['property_id' => $property->id, 'label' => 'Garden feature', 'order' => 0]);

        $this->getJson('/api/v1/public/properties/suggestions?q=garden')->assertJsonPath('data.tags.0', 'Garden feature');
        $tag = PropertyTag::where('property_id', $property->id)->firstOrFail();
        $tag->update(['label' => 'Rooftop feature']);
        $this->getJson('/api/v1/public/properties/suggestions?q=garden')->assertJsonCount(0, 'data.tags');
    }

    public function test_property_pagination_caps_page_size_and_preserves_large_filtered_sets(): void
    {
        for ($index = 1; $index <= 55; $index++) {
            Property::create([
                'slug' => "coast-home-{$index}", 'title' => "Coast Home {$index}", 'location' => 'Coast',
                'price_label' => 'Ask', 'beds' => 3, 'is_published' => true,
            ]);
        }

        $this->getJson('/api/v1/public/properties?q=coast&per_page=20&page=3')
            ->assertOk()
            ->assertJsonCount(15, 'data')
            ->assertJsonPath('meta.current_page', 3)
            ->assertJsonPath('meta.last_page', 3);
        $this->getJson('/api/v1/public/properties?per_page=1000')->assertOk()->assertJsonPath('meta.per_page', 100);
    }
}