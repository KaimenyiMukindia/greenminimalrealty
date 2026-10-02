<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentBlock extends Model
{
    use SoftDeletes;

    protected $fillable = ['page_meta_id', 'key', 'type', 'content', 'order', 'published'];

    protected function casts(): array
    {
        return ['published' => 'boolean'];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(PageMeta::class, 'page_meta_id');
    }
}
