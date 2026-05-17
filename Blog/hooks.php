<?php

use App\Classes\Hook;
use App\Classes\Restatement;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Blog\Models\Article;
use Modules\Blog\Observers\ArticleObserver;

Article::observe(ArticleObserver::class);

Restatement::register('blogs', function () {
    DB::update('update blogs set count_articles = (select count(*) from articles where blogs.id = articles.category_id and active = true)');
    DB::update('update articles set count_comments = (select count(*) from comments where relate_type = "' . Article::$morphName . '" and articles.id = comments.relate_id)');
});

// Регистрация страницы sitemap для статей
SitemapController::$extraPages['articles'] = static function () {
    return Cache::remember('ArticlesSitemap', 600, static function () {
        $articles = Article::query()
            ->active()
            ->orderByDesc('created_at')
            ->limit(10000)
            ->get();

        $locs = [];
        foreach ($articles as $article) {
            $locs[] = [
                'loc'     => route('articles.view', ['slug' => $article->slug]),
                'lastmod' => gmdate('c', $article->created_at),
            ];
        }

        return $locs;
    });
};

// Ссылка блоги в блоке редактора в админке
Hook::add('adminBlockEditor', function (string $content) {
    $urlBlogs = route('admin.blogs.index');
    $urlNew = route('admin.articles.new');
    $labelBlogs = __('index.blogs');
    $labelNew = __('index.new_articles');
    $statsBlogs = statsBlog();
    $statsNew = statsNewArticles();

    return $content
        . '<i class="far fa-circle text-muted"></i> <a href="' . $urlBlogs . '">' . $labelBlogs . '</a> <span class="badge bg-adaptive">' . $statsBlogs . '</span><br>' . PHP_EOL
        . '<i class="far fa-circle text-muted"></i> <a href="' . $urlNew . '">' . $labelNew . '</a> <span class="badge bg-adaptive">' . $statsNew . '</span><br>' . PHP_EOL;
});

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', function (string $content) {
    $url = '/admin/settings?act=blogs';
    $label = __('settings.blogs');

    return $content . '<a class="nav-link" href="' . $url . '" id="blogs">' . $label . '</a>' . PHP_EOL;
});
