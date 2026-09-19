<?php

return [
    'name'        => 'Социальная авторизация',
    'description' => 'Авторизация через социальные сети (Google, GitHub, Yandex, VK)',
    'version'     => '1.0.7',
    'requires'    => '14.7.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/social-auth-settings' => __('social_auth::social_auth.settings'),
        '/admin/socials'              => __('social_auth::social_auth.socials'),
    ],
];
