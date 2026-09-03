<?php

return [
    'name'        => 'Репутация',
    'description' => 'Репутация пользователей с голосованием и историей',
    'version'     => '1.0.3',
    'requires'    => '14.5.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/rating-settings' => __('rating::ratings.settings'),
    ],
];
