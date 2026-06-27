<?php

use Illuminate\Support\Facades\Cache;
use Modules\AdminChat\Models\Chat;

if (! function_exists('statsChat')) {
    function statsChat(): string
    {
        return Cache::remember('statChat', 3600, static function () {
            $total = Chat::query()->count();

            $totalNew = Chat::query()
                ->where('created_at', '>', now()->subDay())
                ->count();

            return formatShortNum($total) . ($totalNew ? '/+' . $totalNew : '');
        });
    }
}

if (! function_exists('statsNewChat')) {
    function statsNewChat(): int
    {
        // Маркер «докуда админ дочитал» — id последнего сообщения (монотонный, без дат)
        return Chat::query()->max('id') ?? 0;
    }
}
