<?php

return [
    'name'        => 'Рассылка',
    'description' => 'Приватная рассылка сообщений выбранным группам пользователей',
    'version'     => '1.0.4',
    'requires'    => '14.7.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/delivery' => 'delivery::delivery.delivery',
    ],
];
