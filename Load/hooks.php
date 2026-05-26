<?php

use App\Classes\Hook;
use App\Classes\Registry;
use Illuminate\Support\Facades\Cache;
use Modules\Load\Models\Down;

Registry::sitemap('downs', static function () {
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
});

// Ссылки на файлы пользователя в анкете
Hook::add('userProfileLinks', static function ($user) {
    return '<li class="list-inline-item"><b><a href="' . route('downs.active-files', ['user' => $user->login]) . '">' . __('load::loads.loads') . '</a></b>'
        . ' (<a href="' . route('downs.active-comments', ['user' => $user->login]) . '">' . __('main.comments') . '</a>)</li>';
});

// Ссылка в боковом меню
Hook::add('sidebarMenu', static function () {
    $expanded = request()->is('loads*', 'downs*') ? ' is-expanded' : '';
    $label = __('load::loads.loads');
    $labelList = __('load::loads.loads_list');
    $labelNew = __('load::loads.new_downs');
    $labelComments = __('load::loads.new_comments');
    $activeList = request()->routeIs('loads.index') ? ' active' : '';
    $activeNew = request()->routeIs('downs.new-files') ? ' active' : '';
    $activeComments = request()->routeIs('downs.new-comments') ? ' active' : '';

    return '<li class="treeview' . $expanded . '">
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
    </li>';
}, 15);

// Ссылка в блоке редактора в админке
Hook::add('adminBlockEditor', static function () {
    return '<div class="col">
        <a href="' . route('admin.loads.index') . '" class="app-tile">
            <div class="app-tile-icon" style="background:#6c757d"><i class="fas fa-download"></i></div>
            <div class="app-tile-label">' . __('load::loads.loads') . '<span class="badge bg-adaptive app-tile-badge">' . statsLoad() . '</span></div>
        </a>
    </div>';
});

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', static fn () => '<a class="nav-link" href="' . route('load.settings') . '">' . __('load::loads.settings') . '</a>');
