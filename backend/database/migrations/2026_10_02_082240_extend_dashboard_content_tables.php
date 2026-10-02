<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('page_metas', function (Blueprint $table): void {
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
        });

        Schema::create('content_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('page_meta_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('type')->default('rich_text');
            $table->longText('content')->nullable();
            $table->unsignedInteger('order')->default(0)->index();
            $table->boolean('published')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['page_meta_id', 'key']);
        });

        Schema::create('menus', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('location')->unique();
            $table->unsignedInteger('order')->default(0)->index();
            $table->boolean('published')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->string('label');
            $table->string('url');
            $table->unsignedInteger('order')->default(0)->index();
            $table->boolean('published')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('airbnb_listings', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->string('hero_image')->nullable();
            $table->json('occupancy_stats')->nullable();
            $table->json('features')->nullable();
            $table->unsignedInteger('order')->default(0)->index();
            $table->boolean('published')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('airbnb_listings');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('content_blocks');
        Schema::table('page_metas', function (Blueprint $table): void {
            $table->dropColumn(['meta_title', 'meta_description']);
        });
    }
};
