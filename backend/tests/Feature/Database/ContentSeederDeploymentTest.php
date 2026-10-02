<?php

namespace Tests\Feature\Database;

use Database\Seeders\ContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ContentSeederDeploymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_seeder_uses_tracked_public_upload_assets_without_the_original_export_folder(): void
    {
        $this->assertFalse(File::isDirectory(base_path('../GreenMinimal')));

        $this->seed(ContentSeeder::class);

        $this->assertDatabaseHas('media', ['path' => 'uploads/logo-BYadtnuV.png']);
        $this->assertDatabaseHas('media', ['path' => 'uploads/hero-CXYyLE1-.jpg']);
        $this->assertDatabaseHas('page_metas', ['route' => '/', 'title' => 'Green Minimal Realty — Sustainable Real Estate in Kenya']);
        $this->assertDatabaseHas('properties', ['slug' => 'watamu-4-bedroom-villa', 'is_published' => true]);
        $this->assertDatabaseHas('menus', ['location' => 'primary']);
    }
}
