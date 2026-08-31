<?php

return [
    'name'        => 'Рассылка',
    'description' => 'Приватная рассылка сообщений выбранным группам пользователей',
    'version'     => '1.0.3',
    'requires'    => '14.3.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/delivery' => __('delivery::delivery.delivery'),
    ],
];
