<?php

use Illuminate\Support\Facades\Cache;
use Modules\Template\Models\Template;

if (! function_exists('statsTemplate')) {
    function statsTemplate(): string
    {
        return Cache::remember('statTemplate', 600, static function () {
            return (string) Template::query()->count();
        });
    }
}
