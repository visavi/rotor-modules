<?php

use Modules\Wall\Models\Wall;
use Modules\Wall\Observers\WallObserver;

return [
    'name'        => 'Стена сообщений',
    'description' => 'Стена сообщений пользователя',
    'version'     => '1.0.0',
    'requires'    => '14.0.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'morphs' => [Wall::class],

    'observers' => [
        Wall::class => WallObserver::class,
    ],

    'spam' => __('wall::walls.wall_posts'),
];
