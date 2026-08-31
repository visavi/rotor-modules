<?php

return [
    'name'        => 'Реклама',
    'description' => 'Платные рекламные ссылки пользователей за деньги или баллы и админские блоки с ограниченным сроком показа',
    'version'     => '1.0.5',
    'requires'    => '14.3.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/admin-adverts'   => __('index.admin_advertising'),
        '/admin/adverts'         => __('index.advertising'),
        '/admin/advert-settings' => __('advert::adverts.settings'),
    ],
];
