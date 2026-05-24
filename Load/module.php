<?php

use Illuminate\Support\Facades\DB;
use Modules\Load\Models\Down;

return [
    'name'        => 'Загрузки',
    'description' => 'Загрузки и файлы',
    'version'     => '1.0.0',
    'requires'    => '14.0.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'models' => [
        Down::class => [
            'label'  => __('load::loads.loads'),
            'search' => ['view' => 'load::search/_downs', 'with' => ['category']],
            'feed'   => ['withs' => ['user', 'files', 'category.parent'], 'view' => 'load::feeds/_downs'],
            'upload' => 'file',
            'rating' => true,
            ],
    ],

    'panel' => [
        '/admin/load-settings' => __('load::loads.settings'),
    ],

    'restatement' => [
        'loads' => function () {
            DB::update('update loads set count_downs = (select count(*) from downs where loads.id = downs.category_id and active = true)');
            DB::update('update downs set count_comments = (select count(*) from comments where relate_type = "' . Down::$morphName . '" and downs.id = comments.relate_id)');
        },
    ],
];
