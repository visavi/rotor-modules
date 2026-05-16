<?php

use Illuminate\Console\Scheduling\Schedule;
use Modules\Board\Models\Item;

return [
    'name'        => 'Объявления',
    'description' => 'Доска объявлений',
    'version'     => '1.0.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'morphs' => [
        Item::class,
    ],

    'searchable' => [
        Item::class => __('board::boards.boards_section'),
    ],

    'feedTypes' => [
        Item::$morphName => [
            'class' => Item::class,
            'withs' => ['user', 'files', 'category.parent'],
        ],
    ],

    'feedViews' => [
        Item::$morphName => 'board::feeds/_items',
    ],

    'searchViews' => [
        Item::$morphName => 'board::search/_items',
    ],

    'schedule' => function (Schedule $schedule) {
        $schedule->command('board:deactivation')->hourly();
    },

    'panel' => [
        '/admin/board-settings' => __('board::boards.settings'),
    ],
];
