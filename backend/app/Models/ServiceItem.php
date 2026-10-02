<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceItem extends Model
{
    use SoftDeletes;
    protected $fillable = ['service_id', 'text', 'order'];
    public function service(): BelongsTo { return $this->belongsTo(Service::class); }
}