<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Blog\Models\Article;
use Modules\Blog\Observers\ArticleObserver;

return [
    'name'        => 'Блоги',
    'description' => 'Статьи по категориям с тегами, комментариями, рейтингом, вложениями и отложенной публикацией',
    'version'     => '1.3.0',
    'requires'    => '14.4.0',
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
            // Отложенные статьи публикуются по расписанию, до этого не считаются
            'stat' => static fn (): Builder => Article::query()->where('active', true),
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
