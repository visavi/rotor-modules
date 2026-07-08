<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Counter\Models\Counter;
use Modules\Counter\Models\Counter31;

if (! function_exists('statsCounter')) {
    /**
     * Возвращает статистику посещений
     */
    function statsCounter(): array
    {
        return Cache::remember('counter', 30, static function () {
            $counter = Counter::query()->first();

            return $counter ? $counter->toArray() : [];
        });
    }
}

if (! function_exists('statsWeek')) {
    /**
     * Возвращает статистику посещений по дням за неделю
     */
    function statsWeek(): Collection
    {
        return Cache::remember('counter_week', 600, static function () {
            return Counter31::query()
                ->orderByDesc('period')
                ->limit(7)
                ->get(['period', 'hosts'])
                ->keyBy('period');
        });
    }
}