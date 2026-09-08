<?php

return [
    'name'        => 'Редактор файлов',
    'description' => 'Редактор файлов, поиск по коду и редактор переводов',
    'version'     => '1.0.4',
    'requires'    => '14.3.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/files'              => 'Редактор файлов',
        '/admin/files/search'       => 'Поиск по коду',
        '/admin/files/translations' => 'Редактор переводов',
    ],
];
