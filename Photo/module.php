<?php

use Illuminate\Support\Facades\DB;
use Modules\Photo\Models\Photo;

return [
    'name'        => 'Галерея',
    'description' => 'Галерея фотографий пользователей с альбомами и комментариями',
    'version'     => '1.3.4',
    'requires'    => '14.7.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'models' => [
        Photo::class => [
            'label'  => 'photo::photos.photos',
            'search' => ['view' => 'photo::search/_photos'],
            'feed'   => ['with' => ['user', 'files'], 'view' => 'photo::feeds/_photos'],
            'upload' => 'media',
            'rating' => true,
            'stat'   => true,
        ],
    ],

    'actions' => [
        '/admin/photos'         => 'photo::photos.photos',
        '/admin/photo-settings' => 'photo::photos.settings',
    ],

    'restatement' => [
        'photos' => function () {
            DB::update('update photos set count_comments = (select count(*) from comments where relate_type = "' . Photo::$morphName . '" and photos.id = comments.relate_id)');
        },
    ],
];
