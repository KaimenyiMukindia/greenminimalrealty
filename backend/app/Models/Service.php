<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;
    protected $fillable = ['slug', 'title', 'short_description', 'icon', 'hero_image', 'order', 'published'];
    protected function casts(): array { return ['published' => 'boolean']; }
    public function items(): HasMany { return $this->hasMany(ServiceItem::class)->orderBy('order'); }
}