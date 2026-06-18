<?php

use Modules\Wall\Models\Wall;
use Modules\Wall\Observers\WallObserver;

return [
    'name'        => 'Стена сообщений',
    'description' => 'Стена сообщений пользователя',
    'version'     => '1.0.1',
    'requires'    => '14.0.1',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'models' => [
        Wall::class => [
            'label' => __('wall::walls.wall_posts'),
            'spam'  => true,
        ],
    ],

    'observers' => [
        Wall::class => WallObserver::class,
    ],
];
