<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Value extends Model
{
    use SoftDeletes;
    protected $fillable = ['icon', 'title', 'description', 'order', 'published'];
    protected function casts(): array { return ['published' => 'boolean']; }
}