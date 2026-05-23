<?php

use Illuminate\Support\Facades\Cache;
use Modules\Offer\Models\Offer;

if (! function_exists('statsOffers')) {
    function statsOffers(): string
    {
        return Cache::remember('offers', 600, static function () {
            $offers = Offer::query()->where('type', 'offer')->count();
            $problems = Offer::query()->where('type', 'issue')->count();

            return sprintf('%d/%d', $offers, $problems);
        });
    }
}
