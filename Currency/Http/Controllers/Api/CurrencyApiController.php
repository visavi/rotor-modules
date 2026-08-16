<?php

declare(strict_types=1);

namespace Modules\Currency\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CurrencyApiController extends Controller
{
    /**
     * Курсы валют ЦБ
     *
     * Отдаётся то, что уже лежит в кэше сайта — к ЦБ ходит сам сайт раз в час,
     * а не каждый клиент
     */
    public function index(): JsonResponse
    {
        $courses = getCurrencyCourses();

        if (! $courses) {
            abort(503, __('currency::currency.error'));
        }

        $rates = [];

        foreach ($courses['Valute'] ?? [] as $code => $valute) {
            $rates[$code] = [
                'name'     => $valute['Name'],
                'nominal'  => $valute['Nominal'],
                'value'    => $valute['Value'],
                'previous' => $valute['Previous'],
            ];
        }

        return response()->json([
            'date' => $courses['Date'] ?? null,
            'data' => $rates,
        ]);
    }
}
