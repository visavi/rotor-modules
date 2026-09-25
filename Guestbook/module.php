<?php

use Modules\Guestbook\Models\Guestbook;

return [
    'name'        => 'Гостевая книга',
    'description' => 'Гостевая книга сайта с модерацией и официальными ответами администрации',
    'version'     => '1.1.4',
    'requires'    => '14.7.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'models' => [
        Guestbook::class => [
            'label'  => 'guestbook::guestbook.guestbook',
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
        '/admin/guestbook'          => 'guestbook::guestbook.guestbook',
        '/admin/guestbook-settings' => 'guestbook::guestbook.settings',
    ],
];
