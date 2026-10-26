<?php

use Illuminate\Support\Facades\Cache;
use Modules\Offer\Models\Offer;

if (! function_exists('statsOffers')) {
    function statsOffers(): string
    {
        return Cache::remember('offers', 600, static function () {
            $counts = Offer::countByType();

            return sprintf('%d/%d', $counts[Offer::OFFER], $counts[Offer::ISSUE]);
        });
    }
}
