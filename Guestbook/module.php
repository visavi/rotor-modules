<?php

use Modules\Guestbook\Models\Guestbook;

return [
    'name'        => 'Гостевая книга',
    'description' => 'Гостевая книга сайта с поддержкой модерации и ответов от администратора',
    'version'     => '1.0.0',
    'requires'    => '14.0.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'models' => [
        Guestbook::class => [
            'search' => ['label' => __('index.guestbook'), 'view' => 'guestbook::search/_guestbooks'],
            'upload' => 'media',
            'spam'   => __('index.guestbook'),
        ],
    ],

    'panel' => [
        '/admin/guestbook'          => __('index.guestbook'),
        '/admin/guestbook-settings' => __('guestbook::guestbook.settings'),
    ],
];
