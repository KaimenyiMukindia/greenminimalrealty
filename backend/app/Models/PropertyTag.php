<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PropertyTag extends Model
{
    use SoftDeletes;
    protected $fillable = ['property_id', 'label', 'order'];
    public function property(): BelongsTo { return $this->belongsTo(Property::class); }
}