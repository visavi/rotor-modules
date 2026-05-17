<?php

use Illuminate\Console\Scheduling\Schedule;
use Modules\Blog\Models\Article;

return [
    'name'        => 'Блоги',
    'description' => 'Блоги и статьи',
    'version'     => '1.0.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'models' => [
        Article::class => [
            'searchable' => __('blog::blogs.blogs_section'),
            'feedType'   => ['withs' => ['user', 'files', 'category.parent']],
            'feedView'   => 'blog::feeds/_articles',
            'searchView' => 'blog::search/_articles',
            'uploadType' => 'media',
            'ratingType' => true,
        ],
    ],

    'panel' => [
        '/admin/blog-settings' => __('blog::blogs.settings'),
    ],

    'schedule' => function (Schedule $schedule) {
        $schedule->command('blog:activation')->everyMinute();
    },
];
