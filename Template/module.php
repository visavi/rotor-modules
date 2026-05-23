<?php

return [
    'name'        => 'Шаблон',
    'description' => 'Минимальный модуль-шаблон для создания новых модулей',
    'version'     => '1.0.0',
    'requires'    => '14.0.0',
    'author'      => 'Автор модуля',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'panel' => [
        '/admin/template' => __('template::template.manage_records'),
    ],
];
