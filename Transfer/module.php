<?php

return [
    'name'        => 'Денежные переводы',
    'description' => 'Переводы денег между пользователями с порогом по баллам и историей операций',
    'version'     => '1.1.1',
    'requires'    => '14.3.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'api' => [
        'transfer' => [
            'point' => 'sendmoneypoint',
        ],
    ],

    'actions' => [
        '/admin/transfer-settings' => __('transfer::transfers.settings'),
    ],
];
