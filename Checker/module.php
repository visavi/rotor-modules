<?php

return [
    'name'        => 'Checker',
    'description' => 'Сканирование файлов сайта: показывает, что появилось, изменилось или пропало со времени прошлой проверки',
    'version'     => '1.0.4',
    'requires'    => '14.7.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/checkers' => 'checker::checker.site_scan',
    ],
];
