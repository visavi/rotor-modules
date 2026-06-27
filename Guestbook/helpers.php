<?php

use Illuminate\Support\Facades\Cache;
use Modules\Guestbook\Models\Guestbook;

if (! function_exists('statsGuestbook')) {
    function statsGuestbook(): string
    {
        return Cache::remember('statGuestbook', 600, static function () {
            $total = Guestbook::query()->count();

            $totalNew = Guestbook::query()
                ->active()
                ->where('created_at', '>', now()->subDay())
                ->count();

            return formatShortNum($total) . ($totalNew ? '/+' . $totalNew : '');
        });
    }
}
