<?php

return [
    'name'        => 'Админ-чат',
    'description' => 'Чат для администраторов сайта',
    'version'     => '1.0.0',
    'requires'    => '14.0.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/chats' => __('admin_chat::admin_chat.admin_chat'),
    ],
];
