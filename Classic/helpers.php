<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Modules\Blog\Models\Article;
use Modules\Classic\Classes\Calendar;
use Modules\Forum\Models\Topic;
use Modules\Load\Models\Down;
use Modules\News\Models\News;
use Modules\Photo\Models\Photo;

if (! function_exists('onlineWidget')) {
    function onlineWidget(): HtmlString
    {
        $online = statsOnline();

        return new HtmlString(view('classic::widgets/_online', compact('online')));
    }
}

if (! function_exists('statsNewsDate')) {
    function statsNewsDate(): string
    {
        $newsDate = Cache::remember('statNewsDate', 900, static function () {
            $news = News::query()->orderByDesc('created_at')->first();

            return $news->created_at ?? 0;
        });

        return $newsDate ? dateFixed($newsDate, 'd.m.Y') : '0';
    }
}

if (! function_exists('pinnedNews')) {
    function pinnedNews(): HtmlString
    {
        $news = Cache::remember('pinnedNews', 1800, static function () {
            return News::query()
                ->where('top', 1)
                ->orderByDesc('created_at')
                ->get();
        });

        return new HtmlString(view('classic::widgets/_news', compact('news')));
    }
}

if (! function_exists('recentTopics')) {
    function recentTopics(int $show = 5): HtmlString
    {
        $topics = Cache::remember('recentTopics', 300, static function () use ($show) {
            return Topic::query()
                ->orderByDesc('updated_at')
                ->limit($show)
                ->get();
        });

        return new HtmlString(view('classic::widgets/_topics', compact('topics')));
    }
}

if (! function_exists('recentDowns')) {
    function recentDowns(int $show = 5): HtmlString
    {
        $downs = Cache::remember('recentDowns', 600, static function () use ($show) {
            return Down::query()
                ->active()
                ->orderByDesc('created_at')
                ->limit($show)
                ->with('category')
                ->get();
        });

        return new HtmlString(view('classic::widgets/_downs', compact('downs')));
    }
}

if (! function_exists('recentArticles')) {
    function recentArticles(int $show = 5): HtmlString
    {
        $articles = Cache::remember('recentArticles', 600, static function () use ($show) {
            return Article::query()
                ->orderByDesc('created_at')
                ->limit($show)
                ->get();
        });

        return new HtmlString(view('classic::widgets/_articles', compact('articles')));
    }
}

if (! function_exists('recentPhotos')) {
    function recentPhotos(int $show = 5): HtmlString
    {
        $photos = Cache::remember('recentPhotos', 1800, static function () use ($show) {
            return Photo::query()
                ->orderByDesc('created_at')
                ->limit($show)
                ->with('files')
                ->get();
        });

        return new HtmlString(view('classic::widgets/_photos', compact('photos')));
    }
}

if (! function_exists('getCalendar')) {
    function getCalendar(?string $period = null): HtmlString
    {
        if ($period === null) {
            $query = request()->query('calendar');
            $period = is_string($query) ? $query : null;
        }

        $calendar = new Calendar();

        return new HtmlString($calendar->getCalendar($period));
    }
}
