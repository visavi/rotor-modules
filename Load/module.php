<?php

use App\Models\File;
use Illuminate\Support\Facades\DB;
use Modules\Load\Models\Down;
use Modules\Load\Observers\FileObserver;

return [
    'name'        => 'Загрузки',
    'description' => 'Загрузки и файлы',
    'version'     => '1.2.0',
    'requires'    => '14.3.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'models' => [
        Down::class => [
            'label'  => __('load::loads.loads'),
            'search' => ['view' => 'load::search/_downs', 'with' => ['category']],
            'feed'   => ['with' => ['user', 'files', 'category.parent'], 'view' => 'load::feeds/_downs'],
            'upload' => 'file',
            'rating' => true,
            // Файлы на модерации в счётчик не идут
            'stat' => static fn (): int => Down::query()->where('active', true)->count(),
        ],
    ],

    'observers' => [
        File::class => FileObserver::class,
    ],

    'actions' => [
        '/admin/loads'         => __('load::loads.loads'),
        '/admin/load-settings' => __('load::loads.settings'),
    ],

    'restatement' => [
        'loads' => function () {
            DB::update('update loads set count_downs = (select count(*) from downs where loads.id = downs.category_id and active = true)');
            DB::update('update downs set count_comments = (select count(*) from comments where relate_type = "' . Down::$morphName . '" and downs.id = comments.relate_id)');
        },
    ],
];
