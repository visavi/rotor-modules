<?php

use Modules\News\Models\News;

return [
    'name'        => 'Новости',
    'description' => 'Новостной раздел сайта с комментариями, RSS и поиском',
    'version'     => '1.0.0',
    'requires'    => '>=14.0.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'morph' => News::class,

    'search' => [
        'label' => __('index.news'),
        'view'  => 'news::search/_news',
    ],
    'feed' => [
        'withs' => ['user', 'files'],
        'view'  => 'news::feeds/_news',
    ],
    'upload' => 'media',
    'rating' => true,

    'panel' => [
        '/admin/news'          => __('index.news'),
        '/admin/news-settings' => __('news::news.settings'),
    ],
];
