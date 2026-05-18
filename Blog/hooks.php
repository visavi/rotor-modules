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

// Ссылка в боковом меню и горизонтальной навигации
Hook::add('sidebarMenuEnd', function (string $content) {
    $active = request()->is('blogs*', 'articles*') ? ' is-expanded' : '';
    $url    = route('blogs.index');
    $label  = __('index.blogs');

    return $content . '<li class="treeview' . $active . '">
        <a class="menu-item" href="#" data-bs-toggle="treeview">
            <i class="menu-icon far fa-sticky-note"></i>
            <span class="menu-label">' . $label . '</span>
            <i class="treeview-indicator fa fa-angle-down"></i>
        </a>
        <ul class="treeview-menu">
            <li><a class="treeview-item' . (request()->routeIs('blogs.index') ? ' active' : '') . '" href="' . $url . '"><i class="icon fas fa-circle fa-xs"></i> ' . __('blog::blogs.blogs_list') . '</a></li>
            <li><a class="treeview-item' . (request()->routeIs('blogs.main') ? ' active' : '') . '" href="' . route('blogs.main') . '"><i class="icon fas fa-circle fa-xs"></i> ' . __('blog::blogs.articles_all') . '</a></li>
            <li><a class="treeview-item' . (request()->routeIs('articles.index') ? ' active' : '') . '" href="' . route('articles.index') . '"><i class="icon fas fa-circle fa-xs"></i> ' . __('blog::blogs.new_articles') . '</a></li>
            <li><a class="treeview-item' . (request()->routeIs('articles.new-comments') ? ' active' : '') . '" href="' . route('articles.new-comments') . '"><i class="icon fas fa-circle fa-xs"></i> ' . __('blog::blogs.new_comments') . '</a></li>
        </ul>
    </li>' . PHP_EOL;
}, 20);

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
    $url = route('blog.settings');
    $label = __('blog::blogs.settings');

    return $content . '<a class="nav-link" href="' . $url . '">' . $label . '</a>' . PHP_EOL;
});
