<?php

use App\Classes\Hook;
use App\Classes\Registry;
use App\Models\Comment;
use Illuminate\Support\Facades\Cache;
use Modules\News\Models\News;

Registry::complaint(News::$morphName, static function (int $id) {
    $model = Comment::query()->find($id);

    return [
        'model' => $model,
        'path'  => $model?->getViewUrl(false),
        'type'  => 'comments',
    ];
});

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
                'lastmod' => gmdate('c', $news->created_at),
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
    $url = route('news.index');
    $active = request()->is('news*') ? ' active' : '';
    $label = __('news::news.news');
    $stats = statsNews();

    return '<li>
        <a class="menu-item' . $active . '" href="' . $url . '">
            <i class="menu-icon far fa-newspaper"></i>
            <span class="menu-label">' . $label . '</span>
            <span class="badge menu-badge">' . $stats . '</span>
        </a>
    </li>';
}, 20);

// Ссылка в колонке footer
Hook::add('footerColumnMiddle', static function () {
    $url = route('news.index');
    $label = __('news::news.news');
    $stats = statsNews();

    return '<li><a class="footer-item" href="' . $url . '">' . $label . '</a> <span class="badge bg-adaptive">' . $stats . '</span></li>';
});

// Ссылка в блоке «Администрирование» в админке
Hook::add('adminBlockAdmin', static function () {
    $url = route('admin.news.index');
    $label = __('news::news.news');
    $stats = statsNews();

    return '<i class="far fa-circle text-muted"></i> <a href="' . $url . '">' . $label . '</a> <span class="badge bg-adaptive">' . $stats . '</span><br>';
});

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', static fn () => '<a class="nav-link" href="' . route('news.settings') . '">' . __('news::news.settings') . '</a>');
