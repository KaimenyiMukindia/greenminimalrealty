<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyImage extends Model
{
    use SoftDeletes;
    protected $fillable = ['property_id', 'media_id', 'order'];
    public function property(): BelongsTo { return $this->belongsTo(Property::class); }
    public function media(): BelongsTo { return $this->belongsTo(Media::class); }
}