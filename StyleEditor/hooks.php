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
    $url = route('admin.editor.index');
    $label = __('style_editor::editor.css_js_editor');

    return '<i class="far fa-circle text-muted"></i> <a href="' . $url . '">' . $label . '</a><br>';
});
