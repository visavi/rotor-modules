<?php

return [
    'name'        => 'Конструктор главной',
    'description' => 'Своя главная страница из блоков: текст, записи разделов, HTML-код и шаблоны',
    'version'     => '1.0.0',
    'requires'    => '14.8.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/home-builder' => 'home_builder::home_builder.title',
    ],
];
