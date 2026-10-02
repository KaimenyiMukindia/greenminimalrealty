<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    protected $fillable = ['path', 'url', 'mime', 'size', 'alt', 'uploaded_by'];

    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}