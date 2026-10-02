<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('site_name')->nullable();
            $table->string('nav_cta_label')->nullable();
            $table->string('nav_cta_url')->nullable();
            $table->string('home_hero_cta_label')->nullable();
            $table->string('home_hero_cta_url')->nullable();
            $table->string('home_footer_cta_label')->nullable();
            $table->string('home_footer_cta_url')->nullable();
            $table->string('whatsapp_label')->nullable();
            $table->string('contact_heading')->nullable();
        });

        DB::table('site_settings')->whereNull('site_name')->update([
            'site_name' => 'Green Minimal Realty',
            'nav_cta_label' => 'Book Consultation',
            'nav_cta_url' => '/contact',
            'home_hero_cta_label' => 'View Properties',
            'home_hero_cta_url' => '/properties',
            'home_footer_cta_label' => 'Book a Consultation',
            'home_footer_cta_url' => '/contact',
            'whatsapp_label' => 'WhatsApp',
            'contact_heading' => 'Reach us',
        ]);
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'site_name', 'nav_cta_label', 'nav_cta_url', 'home_hero_cta_label', 'home_hero_cta_url',
                'home_footer_cta_label', 'home_footer_cta_url', 'whatsapp_label', 'contact_heading',
            ]);
        });
    }
};