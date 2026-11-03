<?php

return [
    'name'        => 'Персональные данные',
    'description' => 'Согласие на обработку персональных данных при регистрации на сайте, через соцсети и API: текст согласия с историей версий и хранение факта согласия',
    'version'     => '1.0.0',
    'requires'    => '14.9.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    // Приложение по разделу privacy в /api/config рисует галочку согласия со ссылкой на текст
    'api' => [
        'privacy' => [
            'required' => true,
            'url'      => static fn (): string => route('privacy.index'),
        ],
    ],

    'actions' => [
        '/admin/privacy' => 'privacy::privacy.admin_title',
    ],
];
