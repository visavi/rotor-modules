<?php

use Illuminate\Support\Facades\DB;
use Modules\News\Models\News;

return [
    'name'        => 'Новости',
    'description' => 'Новостной раздел сайта с комментариями, RSS и поиском',
    'version'     => '1.0.4',
    'requires'    => '14.0.3',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'models' => [
        News::class => [
            'label'  => __('news::news.news'),
            'search' => ['view' => 'news::search/_news'],
            'feed'   => ['with' => ['user', 'files'], 'view' => 'news::feeds/_news'],
            'upload' => 'media',
            'rating' => true,
        ],
    ],

    'actions' => [
        '/admin/news'          => __('news::news.news'),
        '/admin/news-settings' => __('news::news.settings'),
    ],

    'restatement' => [
        'news' => function () {
            DB::update('update news set count_comments = (select count(*) from comments where relate_type = "news" and news.id = comments.relate_id)');
        },
    ],
];
