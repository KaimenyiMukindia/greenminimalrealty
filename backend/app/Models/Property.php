<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use SoftDeletes;
    protected $fillable = ['slug', 'title', 'location', 'price_label', 'price_value', 'beds', 'baths', 'description', 'hero_image', 'is_featured', 'is_published', 'order'];
    protected function casts(): array { return ['price_value' => 'decimal:2', 'is_featured' => 'boolean', 'is_published' => 'boolean']; }
    public function tags(): HasMany { return $this->hasMany(PropertyTag::class)->orderBy('order'); }
    public function images(): HasMany { return $this->hasMany(PropertyImage::class)->with('media')->orderBy('order'); }
}