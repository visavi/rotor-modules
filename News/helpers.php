<?php

use Illuminate\Support\Facades\Cache;
use Modules\News\Models\News;

if (! function_exists('statsNews')) {
    function statsNews(): string
    {
        return Cache::remember('statNews', 300, static function () {
            $total = News::query()->count();

            $totalNew = News::query()
                ->where('created_at', '>', now()->subDay())
                ->count();

            return formatShortNum($total) . ($totalNew ? '/+' . $totalNew : '');
        });
    }
}
