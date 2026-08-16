<?php

return [
    'name'        => 'Рассылка',
    'description' => 'Приват-рассылка сообщений группам пользователей',
    'version'     => '1.0.2',
    'requires'    => '14.3.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/delivery' => __('delivery::delivery.delivery'),
    ],
];
