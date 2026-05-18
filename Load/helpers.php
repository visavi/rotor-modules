<?php

use Illuminate\Support\Facades\Cache;
use Modules\Load\Models\Down;
use Modules\Load\Models\Load;

function statsLoad(): string
{
    return Cache::remember('statLoads', 900, static function () {
        $totalLoads = Load::query()->sum('count_downs');

        $totalNew = Down::query()
            ->active()
            ->where('created_at', '>', strtotime('-1 day', SITETIME))
            ->count();

        return formatShortNum($totalLoads) . ($totalNew ? '/+' . $totalNew : '');
    });
}

function statsNewLoad(): int
{
    return Down::query()->active(false)->count();
}
