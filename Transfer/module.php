<?php

return [
    'name'        => 'Денежные переводы',
    'description' => 'Переводы денег между пользователями',
    'version'     => '1.0.0',
    'requires'    => '14.0.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/transfer-settings' => __('transfer::transfers.settings'),
    ],
];
