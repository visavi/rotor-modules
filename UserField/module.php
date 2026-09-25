<?php

return [
    'name'        => 'Пользовательские поля',
    'description' => 'Дополнительные поля профиля пользователей, настраиваемые администратором',
    'version'     => '1.0.3',
    'requires'    => '14.7.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/user-fields' => 'user_field::user_fields.title',
    ],
];
