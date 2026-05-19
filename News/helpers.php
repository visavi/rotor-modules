<?php

use Illuminate\Support\Facades\Cache;
use Modules\News\Models\News;

function statsNews(): string
{
    return Cache::remember('statNews', 300, static function () {
        $total = News::query()->count();

        $totalNew = News::query()
            ->where('created_at', '>', strtotime('-1 day', SITETIME))
            ->count();

        return formatShortNum($total) . ($totalNew ? '/+' . $totalNew : '');
    });
}
