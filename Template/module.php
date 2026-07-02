<?php

return [
    'name'        => 'Шаблон',
    'description' => 'Минимальный модуль-шаблон для создания новых модулей',
    'version'     => '1.0.1',
    'requires'    => '14.1.0',
    'author'      => 'Автор модуля',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/template' => __('template::template.manage_records'),
    ],
];
