<?php

use Modules\Load\Models\Down;

return [
    'name'        => 'Загрузки',
    'description' => 'Загрузки и файлы',
    'version'     => '1.0.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'morph' => Down::class,

    'search' => [
        'label' => __('load::loads.downs'),
        'view'  => 'load::search/_downs',
    ],
    'feed' => [
        'withs' => ['user', 'files', 'category.parent'],
        'view'  => 'load::feeds/_downs',
    ],
    'upload' => 'file',
    'rating' => true,

    'panel' => [
        '/admin/load-settings' => __('load::loads.settings'),
    ],
];
