<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Support\ContentCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContentObserver
{
    public function __construct(private readonly ContentCache $cache) {}

    public function creating(Model $model): void
    {
        if (in_array('slug', $model->getFillable(), true) && blank($model->slug) && filled($model->title)) $model->slug = Str::slug($model->title);
        if (in_array('order', $model->getFillable(), true) && blank($model->order)) $model->order = (int) $model::max('order') + 1;
    }

    public function created(Model $model): void { $this->record('content.created', $model); }
    public function updated(Model $model): void { $this->record('content.updated', $model); }
    public function deleted(Model $model): void
    {
        $this->record('content.deleted', $model);
    }

    private function record(string $action, Model $model): void
    {
        $this->cache->flush();
        if (app()->runningInConsole() && ! app()->bound('request')) return;
        AuditLog::create(['user_id' => auth()->id(), 'action' => $action, 'ip' => request()->ip(), 'user_agent' => request()->userAgent(), 'meta' => ['model' => $model::class, 'id' => $model->getKey()]]);
    }
}