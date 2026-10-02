<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('menus')->where('location', 'primary')->exists()) return;

        $settings = DB::table('site_settings')->orderBy('id')->first();
        $items = $settings?->nav_items ? json_decode($settings->nav_items, true) : [];
        if (! is_array($items) || $items === []) return;

        $now = now();
        $menuId = DB::table('menus')->insertGetId([
            'name' => 'Primary Navigation',
            'location' => 'primary',
            'order' => 0,
            'published' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach (array_values($items) as $order => $item) {
            if (! is_array($item) || ! isset($item['label']) || ! (isset($item['to']) || isset($item['url']))) continue;
            DB::table('menu_items')->insert([
                'menu_id' => $menuId,
                'label' => (string) $item['label'],
                'url' => (string) ($item['to'] ?? $item['url']),
                'order' => $order,
                'published' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Navigation rows are editable content; retain them if only this data migration is rolled back.
    }
};