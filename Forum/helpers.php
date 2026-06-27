<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Modules\Forum\Models\Post;
use Modules\Forum\Models\Topic;

if (! function_exists('statsForum')) {
    function statsForum(): string
    {
        return Cache::remember('statForums', 600, static function () {
            $topics = Topic::query()->count();
            $posts = Post::query()->count();

            $totalNew = Post::query()
                ->where('created_at', '>', now()->subDay())
                ->count();

            return formatShortNum($topics) . '/' . formatShortNum($posts) . ($totalNew ? '/+' . $totalNew : '');
        });
    }
}

if (! function_exists('progressBar')) {
    /**
     * Выводит прогресс-бар
     */
    function progressBar(int $percent, float|int|string|null $title = null): HtmlString
    {
        if (! $title) {
            $title = $percent . '%';
        }

        return new HtmlString(view('forum::_progressbar', compact('percent', 'title')));
    }
}
