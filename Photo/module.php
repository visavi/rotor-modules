<?php

use Modules\Photo\Models\Photo;

return [
    'name'        => 'Галерея',
    'description' => 'Галерея фотографий пользователей с альбомами и комментариями',
    'version'     => '1.0.0',
    'requires'    => '14.0.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'morph' => Photo::class,

    'search' => [
        'label' => __('photo::photos.photos_section'),
        'view'  => 'photo::search/_photos',
    ],
    'feed' => [
        'withs' => ['user', 'files'],
        'view'  => 'photo::feeds/_photos',
    ],
    'upload' => 'media',
    'rating' => true,

    'panel' => [
        '/admin/photo-settings' => __('photo::photos.settings'),
    ],
];
