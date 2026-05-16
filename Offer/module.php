<?php

use Modules\Offer\Models\Offer;

return [
    'name'        => 'Предложения и проблемы',
    'description' => 'Модуль предложений и проблем',
    'version'     => '1.0.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'models' => [
        Offer::class => [
            'searchable' => __('index.offers'),
            'feedType'   => ['withs' => ['user']],
            'feedView'   => 'offer::feeds/_offers',
            'searchView' => 'offer::search/_offers',
            'ratingType' => true,
        ],
    ],

    'panel' => [
        '/admin/offer-settings' => __('offer::offers.settings'),
    ],
];
