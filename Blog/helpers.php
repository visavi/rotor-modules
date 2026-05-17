<?php

use Illuminate\Support\Facades\Cache;
use Modules\Blog\Models\Article;

if (! function_exists('statsBlog')) {
    function statsBlog(): string
    {
        return Cache::remember('statArticles', 900, static function () {
            $stat = Article::query()->active()->count();
            $totalNew = Article::query()->active()->where('created_at', '>', strtotime('-1 day', SITETIME))->count();

            return formatShortNum($stat) . ($totalNew ? '/+' . $totalNew : '');
        });
    }
}

if (! function_exists('statsNewArticles')) {
    function statsNewArticles(): int
    {
        return Article::query()
            ->active(false)
            ->where('draft', false)
            ->count();
    }
}
