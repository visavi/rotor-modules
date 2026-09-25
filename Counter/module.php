<?php

return [
    'name'        => 'Счетчик посещений',
    'description' => 'Счётчик хостов и хитов с графиками посещаемости и блоком в футере',
    'version'     => '1.0.6',
    'requires'    => '14.7.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/counter-settings' => 'counter::counters.settings',
    ],
];
