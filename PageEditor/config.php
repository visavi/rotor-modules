<?php

$customPath = resource_path('custom');

return [
    'roots' => [
        'views'   => resource_path('views'),
        'lang'    => resource_path('lang'),
        'custom'  => $customPath,
        'modules' => base_path('modules'),
        'assets'  => public_path('assets'),
    ],

    'search_roots' => ['views', 'lang', 'custom', 'modules', 'app'],

    // Корни только для поиска: открыть файл на редактирование нельзя,
    // но найти использование ключа или хука в коде ядра нужно
    'search_only_roots' => [
        'app' => base_path('app'),
    ],

    // Overlay переводов и резервные копии — боевые каталоги, тесты подменяют их через конфиг
    'overlay_path' => $customPath . '/lang',
    'backup_path'  => storage_path('app/page-editor/backups'),

    'editable' => [
        'blade.php', 'php', 'css', 'scss', 'js', 'json',
        'md', 'txt', 'xml', 'svg', 'html', 'yml',
    ],

    // Загрузить можно и то, что редактор не открывает: картинки, шрифты, иконки
    'uploadable' => ['png', 'jpg', 'jpeg', 'gif', 'webp', 'ico', 'woff', 'woff2', 'ttf', 'otf', 'eot'],

    'max_upload_size'    => 5242880,
    'max_edit_size'      => 1048576,
    'max_search_size'    => 1048576,
    'max_search_results' => 500,
];
