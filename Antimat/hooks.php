<?php

use App\Support\Hook;
use App\Support\Registry;
use Modules\Antimat\Models\Antimat;

// Замена нецензурных слов при выводе текста (касты HtmlCast и TextCast)
Registry::textFilter(static fn (string $text): string => Antimat::replace($text));

// Плитка антимата в админ-панели (блок модератора)
Hook::add('adminBlockModer', static function () {
    return '<div class="col">
        <a href="' . route('admin.antimat.index') . '" class="app-tile">
            <div class="app-tile-icon" style="background:#6f42c1"><i class="fas fa-filter"></i></div>
            <div class="app-tile-label">' . __('antimat::antimat.title') . '<span class="badge bg-adaptive app-tile-badge">' . Antimat::query()->count() . '</span></div>
        </a>
    </div>';
});

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', static fn () => '<a class="nav-link" href="' . route('antimat.settings') . '">' . __('antimat::antimat.settings') . '</a>');
