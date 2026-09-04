<?php

use Modules\Wall\Models\Wall;
use Modules\Wall\Observers\WallObserver;

return [
    'name'        => 'Стена сообщений',
    'description' => 'Стена сообщений в профиле пользователя',
    'version'     => '1.1.2',
    'requires'    => '14.5.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'models' => [
        Wall::class => [
            'label' => __('wall::walls.wall_posts'),
            'spam'  => true,
            'stat'  => true,
        ],
    ],

    'observers' => [
        Wall::class => WallObserver::class,
    ],
];
