<?php

return [
    'name'        => 'Реклама',
    'description' => 'Платные рекламные ссылки пользователей за деньги или баллы и админские блоки с ограниченным сроком показа',
    'version'     => '1.0.7',
    'requires'    => '14.7.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/admin-adverts'   => 'index.admin_advertising',
        '/admin/adverts'         => 'index.advertising',
        '/admin/advert-settings' => 'advert::adverts.settings',
    ],
];
