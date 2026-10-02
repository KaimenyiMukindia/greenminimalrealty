<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageMeta extends Model
{
    protected $fillable = ['route', 'title', 'description', 'meta_title', 'meta_description', 'og_image', 'hero_eyebrow', 'hero_headline', 'hero_subheadline', 'hero_image'];

    public function blocks(): HasMany
    {
        return $this->hasMany(ContentBlock::class)->orderBy('order');
    }
}