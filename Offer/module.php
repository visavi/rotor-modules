<?php

use Illuminate\Support\Facades\DB;
use Modules\Offer\Models\Offer;

return [
    'name'        => 'Предложения и проблемы',
    'description' => 'Модуль предложений и проблем',
    'version'     => '1.0.0',
    'requires'    => '14.0.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'morphs' => [Offer::class],

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

    'restatement' => [
        'offers' => function () {
            DB::update('update offers set count_comments = (select count(*) from comments where relate_type = "offers" and offers.id = comments.relate_id)');
        },
    ],
];
