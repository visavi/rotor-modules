<?php

use App\Classes\Hook;
use App\Classes\Registry;
use Illuminate\Support\Facades\Cache;
use Modules\News\Models\News;

Registry::sitemap('news', static function () {
    return Cache::remember('NewsSitemap', 600, static function () {
        $newses = News::query()
            ->orderByDesc('created_at')
            ->limit(10000)
            ->get();

        $locs = [];
        foreach ($newses as $news) {
            $locs[] = [
                'loc'     => route('news.view', ['id' => $news->id]),
                'lastmod' => $news->created_at->format('c'),
            ];
        }

        return $locs;
    });
});

// RSS-ссылка в <head>
Hook::add('head', static function () {
    return '<link href="' . route('news.rss') . '" title="RSS News" type="application/rss+xml" rel="alternate">';
});

// Ссылка в боковом меню
Hook::add('sidebarMenu', static function () {
    return '<li>
        <a class="menu-item' . (request()->is('news*') ? ' active' : '') . '" href="' . route('news.index') . '">
            <i class="menu-icon far fa-newspaper"></i>
            <span class="menu-label">' . __('news::news.news') . '</span>
            <span class="badge menu-badge">' . statsNews() . '</span>
        </a>
    </li>';
}, 20);

// Ссылка в блоке «Администрирование» в админке
Hook::add('adminBlockAdmin', static function () {
    return '<div class="col">
        <a href="' . route('admin.news.index') . '" class="app-tile">
            <div class="app-tile-icon" style="background:#ffc107"><i class="far fa-newspaper"></i></div>
            <div class="app-tile-label">' . __('news::news.news') . '<span class="badge bg-adaptive app-tile-badge">' . statsNews() . '</span></div>
        </a>
    </div>';
});

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', static fn () => '<a class="nav-link" href="' . route('news.settings') . '">' . __('news::news.settings') . '</a>');
