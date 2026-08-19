<?php

return [
    'name'        => 'Пользовательские поля',
    'description' => 'Дополнительные поля профиля пользователей',
    'version'     => '1.0.2',
    'requires'    => '14.3.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/user-fields' => __('user_field::user_fields.title'),
    ],
];
