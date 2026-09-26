<?php

return [
    'name'        => 'Репутация',
    'description' => 'Репутация пользователей с голосованием и историей',
    'version'     => '1.1.0',
    'requires'    => '14.7.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/rating-settings' => 'rating::ratings.settings',
    ],
];
