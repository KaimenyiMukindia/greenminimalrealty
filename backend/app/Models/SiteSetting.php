<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = ['site_name', 'logo', 'address', 'phone', 'email', 'whatsapp_number', 'whatsapp_url', 'whatsapp_label', 'map_url', 'contact_form_labels', 'footer_tagline', 'footer_copyright', 'instagram_url', 'facebook_url', 'linkedin_url', 'nav_cta_label', 'nav_cta_url', 'home_hero_cta_label', 'home_hero_cta_url', 'home_footer_cta_label', 'home_footer_cta_url', 'contact_heading', 'nav_items', 'home_background_image', 'home_content'];

    protected function casts(): array { return ['nav_items' => 'array', 'font_assets' => 'array', 'home_content' => 'array', 'contact_form_labels' => 'array']; }
}