<?php

return [
    'name'        => 'Шаблон',
    'description' => 'Минимальный модуль-шаблон для создания новых модулей',
    'version'     => '1.0.7',
    'requires'    => '14.7.0',
    'author'      => 'Автор модуля',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/template' => 'template::template.manage_records',
    ],
];
