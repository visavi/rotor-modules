<?php

use Modules\Wall\Models\Wall;

return [
    'name'        => 'Стена сообщений',
    'description' => 'Стена сообщений пользователя',
    'version'     => '1.0.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'morph' => Wall::class,
];
