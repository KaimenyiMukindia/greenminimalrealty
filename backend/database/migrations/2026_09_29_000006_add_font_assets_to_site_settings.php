<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void { Schema::table('site_settings', function (Blueprint $table): void { $table->json('font_assets')->nullable()->after('home_background_image'); }); }
    public function down(): void { Schema::table('site_settings', function (Blueprint $table): void { $table->dropColumn('font_assets'); }); }
};