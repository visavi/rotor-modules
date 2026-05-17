<?php

use Modules\Photo\Models\Photo;

return [
    'name'        => 'Галерея',
    'description' => 'Галерея фотографий пользователей с альбомами и комментариями',
    'version'     => '1.0.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'models' => [
        Photo::class => [
            'searchable' => __('photo::photos.photos_section'),
            'feedType'   => ['withs' => ['user', 'files']],
            'feedView'   => 'photo::feeds/_photos',
            'searchView' => 'photo::search/_photos',
            'uploadType' => 'media',
            'ratingType' => true,
        ],
    ],

    'panel' => [
        '/admin/photo-settings' => __('photo::photos.settings'),
    ],
];
