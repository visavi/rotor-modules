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

    'models' => [
        Item::class => [
            'searchable' => __('board::boards.boards_section'),
            'feedType'   => ['withs' => ['user', 'files', 'category.parent']],
            'feedView'   => 'board::feeds/_items',
            'searchView' => 'board::search/_items',
            'uploadType' => 'media',
        ],
    ],

    'panel' => [
        '/admin/board-settings' => __('board::boards.settings'),
    ],

    'schedule' => function (Schedule $schedule) {
        $schedule->command('board:deactivation')->hourly();
    },
];
