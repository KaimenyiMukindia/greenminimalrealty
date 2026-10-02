<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AirbnbListing extends Model
{
    use SoftDeletes;

    protected $fillable = ['slug', 'title', 'location', 'description', 'hero_image', 'occupancy_stats', 'features', 'order', 'published'];

    protected function casts(): array
    {
        return ['occupancy_stats' => 'array', 'features' => 'array', 'published' => 'boolean'];
    }
}
