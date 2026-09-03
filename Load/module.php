<?php

use App\Models\File;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Load\Models\Down;
use Modules\Load\Observers\FileObserver;

return [
    'name'        => 'Загрузки',
    'description' => 'Файловый архив по категориям с модерацией загрузок, скриншотами, комментариями и рейтингом',
    'version'     => '1.4.1',
    'requires'    => '14.5.0',
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
            'stat' => static fn (): Builder => Down::query()->where('active', true),
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
