<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_metas', function (Blueprint $table): void { $table->string('hero_image')->nullable()->after('hero_subheadline'); });
        Schema::table('site_settings', function (Blueprint $table): void { $table->string('home_background_image')->nullable()->after('nav_items'); });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void { $table->dropColumn('home_background_image'); });
        Schema::table('page_metas', function (Blueprint $table): void { $table->dropColumn('hero_image'); });
    }
};