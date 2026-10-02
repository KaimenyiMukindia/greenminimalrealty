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
            $table->string('map_url', 2048)->nullable();
            $table->json('contact_form_labels')->nullable();
        });

        DB::table('site_settings')->whereNull('map_url')->update([
            'map_url' => 'https://www.google.com/maps?q=Serena%20Road%20Shanzu%20Mombasa&output=embed',
            'contact_form_labels' => json_encode([
                'success' => 'Message sent.',
                'name' => 'Full name',
                'email' => 'Email',
                'phone' => 'Phone',
                'interest' => 'I am interested in',
                'message' => 'Tell us a little more',
                'submit' => 'Send message',
            ], JSON_THROW_ON_ERROR),
        ]);
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn(['map_url', 'contact_form_labels']);
        });
    }
};