<?php

use Illuminate\Console\Scheduling\Schedule;
use Modules\Blog\Models\Article;

return [
    'name'        => 'Блоги',
    'description' => 'Блоги и статьи',
    'version'     => '1.0.0',
    'requires'    => '>=13.0.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'morph' => Article::class,

    'search' => [
        'label' => __('blog::blogs.blogs_section'),
        'view'  => 'blog::search/_articles',
    ],
    'feed' => [
        'withs' => ['user', 'files', 'category.parent'],
        'view'  => 'blog::feeds/_articles',
    ],
    'upload' => 'media',
    'rating' => true,

    'panel' => [
        '/admin/blog-settings' => __('blog::blogs.settings'),
    ],

    'schedule' => function (Schedule $schedule) {
        $schedule->command('blog:activation')->everyMinute();
    },
];
