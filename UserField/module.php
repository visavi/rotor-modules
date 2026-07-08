<?php

return [
    'name'        => 'Пользовательские поля',
    'description' => 'Дополнительные поля профиля пользователей',
    'version'     => '1.0.0',
    'requires'    => '14.1.2',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/user-fields' => __('user_field::user_fields.title'),
    ],
];
