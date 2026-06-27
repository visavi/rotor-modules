<?php

use Illuminate\Support\Facades\Cache;
use Modules\Photo\Models\Photo;

if (! function_exists('statsPhotos')) {
    function statsPhotos(): string
    {
        return Cache::remember('statPhotos', 900, static function () {
            $stat = Photo::query()->count();
            $totalNew = Photo::query()->where('created_at', '>', now()->subDay())->count();

            return formatShortNum($stat) . ($totalNew ? '/+' . $totalNew : '');
        });
    }
}
