<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Modules\Blog\Models\Article;
use Modules\Blog\Observers\ArticleObserver;

return [
    'name'        => 'Блоги',
    'description' => 'Блоги и статьи',
    'version'     => '1.0.3',
    'requires'    => '14.0.3',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'models' => [
        Article::class => [
            'label'  => __('blog::blogs.blogs'),
            'search' => ['view' => 'blog::search/_articles', 'with' => ['category']],
            'feed'   => ['with' => ['user', 'files', 'category.parent'], 'view' => 'blog::feeds/_articles'],
            'upload' => 'media',
            'rating' => true,
        ],
    ],

    'observers' => [
        Article::class => ArticleObserver::class,
    ],

    'actions' => [
        '/admin/blogs'         => __('blog::blogs.blogs'),
        '/admin/blog-settings' => __('blog::blogs.settings'),
    ],

    'schedule' => function (Schedule $schedule) {
        $schedule->command('blog:activation')->everyMinute();
    },

    'restatement' => [
        'blogs' => function () {
            DB::update('update blogs set count_articles = (select count(*) from articles where blogs.id = articles.category_id and active = true)');
            DB::update('update articles set count_comments = (select count(*) from comments where relate_type = "' . Article::$morphName . '" and articles.id = comments.relate_id)');
        },
    ],
];
