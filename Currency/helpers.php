<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;

if (! function_exists('getCurrencyRates')) {
    function getCurrencyRates(): HtmlString
    {
        $courses = Cache::remember('currency', 3600, static function () {
            try {
                $response = Http::timeout(3)
                    ->get('https://www.cbr-xml-daily.ru/daily_json.js');

                return $response->json();
            } catch (Exception) {
                return null;
            }
        });

        return new HtmlString(view('currency::_rates', compact('courses')));
    }
}
