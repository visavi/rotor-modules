<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Modules\Board\Models\Item;

if (! function_exists('statsBoard')) {
    function statsBoard(): string
    {
        return Cache::remember('statBoards', 900, static function () {
            $stat = formatShortNum(Item::query()->where('expires_at', '>', SITETIME)->count());
            $totalNew = Item::query()->where('updated_at', '>', strtotime('-1 day', SITETIME))->count();

            return formatShortNum($stat) . ($totalNew ? '/+' . $totalNew : '');
        });
    }
}

if (! function_exists('recentBoards')) {
    function recentBoards(int $show = 5): HtmlString
    {
        $items = Cache::remember('recentBoards', 600, static function () use ($show) {
            return Item::query()
                ->where('expires_at', '>', SITETIME)
                ->orderByDesc('created_at')
                ->limit($show)
                ->get();
        });

        return new HtmlString(view('board::widgets/_boards', compact('items')));
    }
}
