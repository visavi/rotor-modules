<?php

use App\Classes\Hook;

// Плитка очистки кеша в админ-панели (блок босса)
Hook::add('adminBlockBoss', static function () {
    return '<div class="col">
        <a href="' . route('admin.caches.index') . '" class="app-tile">
            <div class="app-tile-icon" style="background:#198754"><i class="fas fa-broom"></i></div>
            <div class="app-tile-label">' . __('caches::caches.title') . '</div>
        </a>
    </div>';
});
