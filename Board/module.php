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

    'morph' => Item::class,

    'search' => [
        'label' => __('board::boards.boards_section'),
        'view'  => 'board::search/_items',
    ],

    'feed' => [
        'withs' => ['user', 'files', 'category.parent'],
        'view'  => 'board::feeds/_items',
    ],

    'upload' => 'media',

    'panel' => [
        '/admin/board-settings' => __('board::boards.settings'),
    ],

    'schedule' => function (Schedule $schedule) {
        $schedule->command('board:deactivation')->hourly();
    },
];
