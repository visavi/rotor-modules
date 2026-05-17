<?php

use Illuminate\Support\Facades\Cache;
use Modules\Photo\Models\Photo;

function statsPhotos(): string
{
    return Cache::remember('statPhotos', 900, static function () {
        $stat = Photo::query()->count();
        $totalNew = Photo::query()->where('created_at', '>', strtotime('-1 day', SITETIME))->count();

        return formatShortNum($stat) . ($totalNew ? '/+' . $totalNew : '');
    });
}
