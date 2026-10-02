<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('logo')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('whatsapp_url')->nullable();
            $table->text('footer_tagline')->nullable();
            $table->string('footer_copyright')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->json('nav_items')->nullable();
            $table->timestamps();
        });

        Schema::create('page_metas', function (Blueprint $table): void {
            $table->id();
            $table->string('route')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('og_image')->nullable();
            $table->string('hero_eyebrow')->nullable();
            $table->string('hero_headline')->nullable();
            $table->text('hero_subheadline')->nullable();
            $table->timestamps();
        });

        Schema::create('media', function (Blueprint $table): void {
            $table->id();
            $table->string('path');
            $table->string('url');
            $table->string('mime');
            $table->unsignedInteger('size');
            $table->string('alt')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('stats', function (Blueprint $table): void {
            $table->id();
            $table->enum('section', ['hero', 'airbnb']);
            $table->string('value');
            $table->string('label');
            $table->unsignedInteger('order')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['section', 'order']);
        });

        Schema::create('values', function (Blueprint $table): void {
            $table->id();
            $table->string('icon')->nullable();
            $table->string('title');
            $table->text('description');
            $table->unsignedInteger('order')->default(0)->index();
            $table->boolean('published')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('services', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('short_description');
            $table->string('icon')->nullable();
            $table->string('hero_image')->nullable();
            $table->unsignedInteger('order')->default(0)->index();
            $table->boolean('published')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('service_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->string('text');
            $table->unsignedInteger('order')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['service_id', 'order']);
        });

        Schema::create('properties', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('location');
            $table->string('price_label');
            $table->decimal('price_value', 14, 2)->nullable();
            $table->unsignedSmallInteger('beds')->nullable();
            $table->unsignedSmallInteger('baths')->nullable();
            $table->text('description')->nullable();
            $table->string('hero_image')->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_published')->default(true)->index();
            $table->unsignedInteger('order')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('property_tags', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->unsignedInteger('order')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('property_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['property_id', 'media_id']);
        });

        Schema::create('sustainability_pillars', function (Blueprint $table): void {
            $table->id();
            $table->string('icon')->nullable();
            $table->string('title');
            $table->text('description');
            $table->unsignedInteger('order')->default(0)->index();
            $table->boolean('published')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('testimonials', function (Blueprint $table): void {
            $table->id();
            $table->text('quote');
            $table->string('author_name');
            $table->string('author_role')->nullable();
            $table->unsignedInteger('order')->default(0)->index();
            $table->boolean('published')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('contact_submissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('interest')->nullable();
            $table->text('message');
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_submissions');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('sustainability_pillars');
        Schema::dropIfExists('property_images');
        Schema::dropIfExists('property_tags');
        Schema::dropIfExists('properties');
        Schema::dropIfExists('service_items');
        Schema::dropIfExists('services');
        Schema::dropIfExists('values');
        Schema::dropIfExists('stats');
        Schema::dropIfExists('media');
        Schema::dropIfExists('page_metas');
        Schema::dropIfExists('site_settings');
    }
};