<?php

use Modules\UserLocation\Middleware\TrackUserLocation;

return [
    'aliases' => [
        'track.location' => TrackUserLocation::class,
    ],
    'web' => [
        TrackUserLocation::class,
    ],
];
