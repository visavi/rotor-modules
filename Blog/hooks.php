<?php

use App\Classes\Hook;
use App\Classes\Registry;
use Illuminate\Support\Facades\Cache;
use Modules\Blog\Models\Article;

// Регистрация страницы sitemap для статей
Registry::sitemap('articles', static function () {
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
});

// Ссылки на публикации пользователя в анкете
Hook::add('userProfileLinks', static function ($user) {
    return '<li class="list-inline-item"><b><a href="' . route('articles.user-articles', ['user' => $user->login]) . '">' . __('index.blogs') . '</a></b>'
        . ' (<a href="' . route('articles.user-comments', ['user' => $user->login]) . '">' . __('main.comments') . '</a>)</li>';
});

// Ссылка в боковом меню и горизонтальной навигации
Hook::add('sidebarMenu', static function () {
    $active = request()->is('blogs*', 'articles*') ? ' is-expanded' : '';
    $url = route('blogs.index');
    $label = __('index.blogs');

    return '<li class="treeview' . $active . '">
        <a class="menu-item" href="' . $url . '" data-bs-toggle="treeview">
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
    </li>';
}, 15);

// Ссылка блоги в блоке редактора в админке
Hook::add('adminBlockEditor', static function () {
    $urlBlogs = route('admin.blogs.index');
    $urlNew = route('admin.articles.new');
    $labelBlogs = __('index.blogs');
    $labelNew = __('index.new_articles');
    $statsBlogs = statsBlog();
    $statsNew = statsNewArticles();

    return '<i class="far fa-circle text-muted"></i> <a href="' . $urlBlogs . '">' . $labelBlogs . '</a> <span class="badge bg-adaptive">' . $statsBlogs . '</span><br>' . PHP_EOL
        . '<i class="far fa-circle text-muted"></i> <a href="' . $urlNew . '">' . $labelNew . '</a> <span class="badge bg-adaptive">' . $statsNew . '</span><br>';
});

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', static fn () => '<a class="nav-link" href="' . route('blog.settings') . '">' . __('blog::blogs.settings') . '</a>');
