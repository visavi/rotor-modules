<?php

return [
    'name'        => 'Редактор CSS/JS',
    'description' => 'Редактор пользовательских стилей и скриптов сайта',
    'version'     => '1.0.3',
    'requires'    => '14.7.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/editor' => 'style_editor::editor.css_js_editor',
    ],
];
