<?php

use Modules\Offer\Models\Offer;

return [
    'name'        => 'Предложения и проблемы',
    'description' => 'Модуль предложений и проблем',
    'version'     => '1.0.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'morph' => Offer::class,

    'search' => [
        'label' => __('index.offers'),
        'view'  => 'offer::search/_offers',
    ],
    'feed' => [
        'withs' => ['user'],
        'view'  => 'offer::feeds/_offers',
    ],
    'rating' => true,

    'panel' => [
        '/admin/offer-settings' => __('offer::offers.settings'),
    ],
];
