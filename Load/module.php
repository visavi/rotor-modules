<?php

use Modules\Load\Models\Down;

return [
    'name'        => 'Загрузки',
    'description' => 'Загрузки и файлы',
    'version'     => '1.0.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'models' => [
        Down::class => [
            'searchable' => __('load::loads.downs'),
            'feedType'   => ['withs' => ['user', 'files', 'category.parent']],
            'feedView'   => 'load::feeds/_downs',
            'searchView' => 'load::search/_downs',
            'uploadType' => 'file',
            'ratingType' => true,
        ],
    ],

    'panel' => [
        '/admin/load-settings' => __('load::loads.settings'),
    ],
];
