<?php

return [
    'name'        => 'Счетчик посещений',
    'description' => 'Счетчик хостов и хитов с графиками и блоком в футере',
    'version'     => '1.0.0',
    'requires'    => '14.1.2',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/counter-settings' => __('counter::counters.settings'),
    ],
];
