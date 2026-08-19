<?php

return [
    'name'        => 'Checker',
    'description' => 'Сканирование файловой системы сайта',
    'version'     => '1.0.3',
    'requires'    => '14.3.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/checkers' => __('checker::checker.site_scan'),
    ],
];
