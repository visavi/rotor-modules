<?php

use Illuminate\Support\Facades\DB;
use Modules\Photo\Models\Photo;

return [
    'name'        => 'Галерея',
    'description' => 'Галерея фотографий пользователей с альбомами и комментариями',
    'version'     => '1.0.0',
    'requires'    => '14.0.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'models' => [
        Photo::class => [
            'search' => ['view' => 'photo::search/_photos'],
            'feed'   => ['withs' => ['user', 'files'], 'view' => 'photo::feeds/_photos'],
            'upload' => 'media',
            'rating' => true,
            ],
    ],

    'panel' => [
        '/admin/photo-settings' => __('photo::photos.settings'),
    ],

    'restatement' => [
        'photos' => function () {
            DB::update('update photos set count_comments = (select count(*) from comments where relate_type = "' . Photo::$morphName . '" and photos.id = comments.relate_id)');
        },
    ],
];
