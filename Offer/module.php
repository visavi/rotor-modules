<?php

use Illuminate\Support\Facades\DB;
use Modules\Offer\Models\Offer;

return [
    'name'        => 'Предложения и проблемы',
    'description' => 'Модуль предложений и проблем',
    'version'     => '1.0.3',
    'requires'    => '14.0.3',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'models' => [
        Offer::class => [
            'label'  => __('offer::offers.section'),
            'search' => ['view' => 'offer::search/_offers'],
            'feed'   => ['with' => ['user'], 'view' => 'offer::feeds/_offers'],
            'rating' => true,
        ],
    ],

    'actions' => [
        '/admin/offers'         => __('offer::offers.section'),
        '/admin/offer-settings' => __('offer::offers.settings'),
    ],

    'restatement' => [
        'offers' => function () {
            DB::update('update offers set count_comments = (select count(*) from comments where relate_type = "offers" and offers.id = comments.relate_id)');
        },
    ],
];
