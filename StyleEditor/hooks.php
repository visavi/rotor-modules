<?php

use App\Classes\Hook;

// Подключение пользовательского CSS
Hook::add('head', function () {
    $path = public_path('assets/custom.css');
    if (file_exists($path) && filesize($path) > 0) {
        return '<link rel="stylesheet" href="/assets/custom.css?v=' . filemtime($path) . '">';
    }

    return null;
}, -1);

// Подключение пользовательского JS
Hook::add('footer', function () {
    $path = public_path('assets/custom.js');
    if (file_exists($path) && filesize($path) > 0) {
        return '<script src="/assets/custom.js?v=' . filemtime($path) . '"></script>';
    }

    return null;
}, -1);

// Ссылка в блоке admin в админке
Hook::add('adminBlockAdmin', static function () {
    return '<div class="col">
        <a href="' . route('admin.editor.index') . '" class="app-tile">
            <div class="app-tile-icon" style="background:#e3015d"><i class="fas fa-paint-brush"></i></div>
            <div class="app-tile-label">' . __('style_editor::editor.css_js_editor') . '</div>
        </a>
    </div>';
});
