<?php

return [
    'name'        => 'Админ-чат',
    'description' => 'Закрытый чат администрации сайта, доступный только из админ-панели',
    'version'     => '1.0.6',
    'requires'    => '14.7.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/chats' => 'admin_chat::admin_chat.admin_chat',
    ],
];
