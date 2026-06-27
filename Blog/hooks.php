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
                'lastmod' => $article->created_at->format('c'),
            ];
        }

        return $locs;
    });
});

// Ссылки на публикации пользователя в анкете
Hook::add('userProfileLinks', static function ($user) {
    return '<li class="list-inline-item"><b><a href="' . route('articles.user-articles', ['user' => $user->login]) . '">' . __('blog::blogs.blogs') . '</a></b>'
        . ' (<a href="' . route('articles.user-comments', ['user' => $user->login]) . '">' . __('main.comments') . '</a>)</li>';
});

// Ссылка в боковом меню и горизонтальной навигации
Hook::add('sidebarMenu', static function () {
    return '<li class="treeview' . (request()->is('blogs*', 'articles*') ? ' is-expanded' : '') . '">
        <a class="menu-item" href="' . route('blogs.index') . '" data-bs-toggle="treeview">
            <i class="menu-icon far fa-sticky-note"></i>
            <span class="menu-label">' . __('blog::blogs.blogs') . '</span>
            <i class="treeview-indicator fa fa-angle-down"></i>
        </a>
        <ul class="treeview-menu">
            <li><a class="treeview-item' . (request()->routeIs('blogs.index') ? ' active' : '') . '" href="' . route('blogs.index') . '"><i class="icon fas fa-circle fa-xs"></i> ' . __('blog::blogs.blogs_list') . '</a></li>
            <li><a class="treeview-item' . (request()->routeIs('blogs.main') ? ' active' : '') . '" href="' . route('blogs.main') . '"><i class="icon fas fa-circle fa-xs"></i> ' . __('blog::blogs.articles_all') . '</a></li>
            <li><a class="treeview-item' . (request()->routeIs('articles.index') ? ' active' : '') . '" href="' . route('articles.index') . '"><i class="icon fas fa-circle fa-xs"></i> ' . __('blog::blogs.new_articles') . '</a></li>
            <li><a class="treeview-item' . (request()->routeIs('articles.new-comments') ? ' active' : '') . '" href="' . route('articles.new-comments') . '"><i class="icon fas fa-circle fa-xs"></i> ' . __('blog::blogs.new_comments') . '</a></li>
        </ul>
    </li>';
}, 15);

// Ссылка блоги в блоке редактора в админке
Hook::add('adminBlockEditor', static function () {
    return '<div class="col">
        <a href="' . route('admin.blogs.index') . '" class="app-tile">
            <div class="app-tile-icon" style="background:#0d6efd"><i class="far fa-sticky-note"></i></div>
            <div class="app-tile-label">' . __('blog::blogs.blogs') . '<span class="badge bg-adaptive app-tile-badge">' . statsBlog() . '</span></div>
        </a>
    </div>
    <div class="col">
        <a href="' . route('admin.articles.new') . '" class="app-tile">
            <div class="app-tile-icon" style="background:#ffc107"><i class="fas fa-pen-nib"></i></div>
            <div class="app-tile-label">' . __('blog::blogs.new_articles') . '<span class="badge bg-adaptive app-tile-badge">' . statsNewArticles() . '</span></div>
        </a>
    </div>';
});

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', static fn () => '<a class="nav-link" href="' . route('blog.settings') . '">' . __('blog::blogs.settings') . '</a>');
