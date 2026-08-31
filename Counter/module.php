<?php

return [
    'name'        => 'Счетчик посещений',
    'description' => 'Счётчик хостов и хитов с графиками посещаемости и блоком в футере',
    'version'     => '1.0.3',
    'requires'    => '14.3.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/counter-settings' => __('counter::counters.settings'),
    ],
];
