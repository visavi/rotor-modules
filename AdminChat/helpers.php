<?php

use Illuminate\Support\Facades\Cache;
use Modules\AdminChat\Models\Chat;

if (! function_exists('statsChat')) {
    function statsChat(): string
    {
        return Cache::remember('statChat', 3600, static function () {
            $total = Chat::query()->count();

            $totalNew = Chat::query()
                ->where('created_at', '>', strtotime('-1 day', SITETIME))
                ->count();

            return formatShortNum($total) . ($totalNew ? '/+' . $totalNew : '');
        });
    }
}

if (! function_exists('statsNewChat')) {
    function statsNewChat(): int
    {
        return Chat::query()->max('created_at') ?? 0;
    }
}
