<?php

use App\Classes\Hook;
use App\Classes\Restatement;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Load\Models\Down;

SitemapController::$extraPages['downs'] = static function () {
    return Cache::remember('DownsSitemap', 600, static function () {
        $downs = Down::query()
            ->active()
            ->orderByDesc('created_at')
            ->limit(10000)
            ->get();

        $locs = [];
        foreach ($downs as $down) {
            $locs[] = [
                'loc'     => route('downs.view', ['id' => $down->id]),
                'lastmod' => gmdate('c', $down->created_at),
            ];
        }

        return $locs;
    });
};

Restatement::register('loads', function () {
    DB::update('update loads set count_downs = (select count(*) from downs where loads.id = downs.category_id and active = true)');
    DB::update('update downs set count_comments = (select count(*) from comments where relate_type = "' . Down::$morphName . '" and downs.id = comments.relate_id)');
});

// Ссылки на файлы пользователя в анкете
Hook::add('userProfileLinks', function (string $content, $user) {
    $link = ' / <b><a href="' . route('downs.active-files', ['user' => $user->login]) . '">' . __('index.loads') . '</a></b>'
        . ' (<a href="' . route('downs.active-comments', ['user' => $user->login]) . '">' . __('main.comments') . '</a>)';

    return $content . $link;
});

// Ссылка в боковом меню
Hook::add('sidebarMenuEnd', function (string $content) {
    $expanded = request()->is('loads*', 'downs*') ? ' is-expanded' : '';
    $label = __('index.loads');
    $labelList = __('load::loads.loads_list');
    $labelNew = __('load::loads.new_downs');
    $labelComments = __('load::loads.new_comments');
    $activeList = request()->routeIs('loads.index') ? ' active' : '';
    $activeNew = request()->routeIs('downs.new-files') ? ' active' : '';
    $activeComments = request()->routeIs('downs.new-comments') ? ' active' : '';

    return $content . '<li class="treeview' . $expanded . '">
        <a class="menu-item" href="' . route('loads.index') . '" data-bs-toggle="treeview">
            <i class="menu-icon fas fa-download"></i>
            <span class="menu-label">' . $label . '</span>
            <i class="treeview-indicator fa fa-angle-down"></i>
        </a>
        <ul class="treeview-menu">
            <li><a class="treeview-item' . $activeList . '" href="' . route('loads.index') . '"><i class="icon fas fa-circle fa-xs"></i> ' . $labelList . '</a></li>
            <li><a class="treeview-item' . $activeNew . '" href="' . route('downs.new-files') . '"><i class="icon fas fa-circle fa-xs"></i> ' . $labelNew . '</a></li>
            <li><a class="treeview-item' . $activeComments . '" href="' . route('downs.new-comments') . '"><i class="icon fas fa-circle fa-xs"></i> ' . $labelComments . '</a></li>
        </ul>
    </li>' . PHP_EOL;
}, 10);

// Ссылка в блоке редактора в админке
Hook::add('adminBlockEditor', function (string $content) {
    $url = route('admin.loads.index');
    $label = __('index.loads');
    $stats = statsLoad();

    return $content
        . '<i class="far fa-circle text-muted"></i> <a href="' . $url . '">' . $label . '</a> <span class="badge bg-adaptive">' . $stats . '</span><br>' . PHP_EOL;
});

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', function (string $content) {
    $url = route('load.settings');
    $label = __('load::loads.settings');

    return $content . '<a class="nav-link" href="' . $url . '">' . $label . '</a>' . PHP_EOL;
});
