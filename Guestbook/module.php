<?php

use Modules\Guestbook\Models\Guestbook;

return [
    'name'        => 'Гостевая книга',
    'description' => 'Гостевая книга сайта с модерацией и официальными ответами администрации',
    'version'     => '1.1.1',
    'requires'    => '14.5.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'models' => [
        Guestbook::class => [
            'label'  => __('guestbook::guestbook.guestbook'),
            'search' => ['view' => 'guestbook::search/_guestbooks'],
            'upload' => 'media',
            'spam'   => true,
            'stat'   => true,
        ],
    ],

    'api' => [
        'guestbook' => [
            'text_min' => 'guestbook_text_min',
            'text_max' => 'guestbook_text_max',
        ],
    ],

    'actions' => [
        '/admin/guestbook'          => __('guestbook::guestbook.guestbook'),
        '/admin/guestbook-settings' => __('guestbook::guestbook.settings'),
    ],
];
