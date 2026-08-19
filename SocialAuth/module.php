<?php

return [
    'name'        => 'Социальная авторизация',
    'description' => 'Авторизация через социальные сети (Google, GitHub, Yandex, VK)',
    'version'     => '1.0.5',
    'requires'    => '14.3.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/social-auth-settings' => __('social_auth::social_auth.settings'),
    ],
];
