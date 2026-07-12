<?php

return [
    'name'        => 'Социальная авторизация',
    'description' => 'Авторизация через социальные сети (Google, GitHub, Yandex, VK)',
    'version'     => '1.0.3',
    'requires'    => '14.1.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/social-auth-settings' => __('social_auth::social_auth.settings'),
    ],
];
