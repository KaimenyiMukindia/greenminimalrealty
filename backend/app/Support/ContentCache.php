<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class ContentCache
{
    private const INDEX = 'public-content-cache-keys';

    public function remember(string $key, \Closure $callback, int $seconds = 300): mixed
    {
        $keys = Cache::get(self::INDEX, []);
        if (! in_array($key, $keys, true)) {
            $keys[] = $key;
            Cache::put(self::INDEX, $keys, $seconds);
        }
        return Cache::remember($key, $seconds, $callback);
    }

    public function flush(): void
    {
        foreach (Cache::get(self::INDEX, []) as $key) Cache::forget($key);
        Cache::forget(self::INDEX);
    }
}