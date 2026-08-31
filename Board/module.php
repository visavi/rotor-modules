<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Board\Models\Item;

return [
    'name'        => 'Объявления',
    'description' => 'Доска объявлений',
    'version'     => '1.2.0',
    'requires'    => '14.4.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'models' => [
        Item::class => [
            'label'  => __('board::boards.boards'),
            'search' => ['view' => 'board::search/_items'],
            'feed'   => [
                'with'  => ['user', 'files', 'category.parent'],
                'view'  => 'board::feeds/_items',
                'scope' => fn ($query) => $query->where('active', true)->where('expires_at', '>', now()),
                'api'   => fn (Item $post): array => ['price' => $post->price, 'phone' => $post->phone],
            ],
            'upload' => 'media',
            // Только живые объявления: скрытые и просроченные не считаем
            'stat' => static fn (): Builder => Item::query()
                ->where('active', true)
                ->where('expires_at', '>', now()),
        ],
    ],

    'actions' => [
        '/admin/boards'         => __('board::boards.boards'),
        '/admin/board-settings' => __('board::boards.settings'),
    ],

    'schedule' => function (Schedule $schedule) {
        $schedule->command('board:deactivation')->hourly();
    },

    'restatement' => [
        'boards' => function () {
            DB::update('update boards set count_items = (select count(*) from items where boards.id = items.board_id and items.active = true and items.expires_at >= ?)', [now()]);
        },
    ],
];
