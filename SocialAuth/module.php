<?php

return [
    'name'        => 'SocialAuth',
    'description' => 'Авторизация через социальные сети (Google, GitHub, Yandex)',
    'version'     => '1.0.0',
    'requires'    => '14.0.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/social-auth-settings' => __('social_auth::social_auth.settings'),
    ],
];
