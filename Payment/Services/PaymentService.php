<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use App\Models\PaidAdvert;
use Modules\Payment\Models\Order;

class PaymentService
{
    /**
     * Create paid advert
     */
    public function createAdvert(Order $order): PaidAdvert
    {
        $advert = PaidAdvert::query()->create([
            'user_id'    => $order->user_id,
            'place'      => $order->data['place'],
            'site'       => $order->data['site'],
            'names'      => $order->data['names'],
            'color'      => $order->data['color'],
            'bold'       => $order->data['bold'],
            'comment'    => $order->data['description'] . ' #' . $order->id . ' ' . $order->data['comment'],
            'created_at' => SITETIME,
            'deleted_at' => strtotime('+' . $order->data['term'] . ' days', SITETIME),
        ]);

        clearCache('paidAdverts');

        return $advert;
    }

    /**
     * Calculate
     */
    public function calculateAdvert(array $data): array
    {
        $countNames = count($data['names']);

        $placePrice = config('payment.prices.places.' . $data['place']) ?? 0;
        $colorPrice = config('payment.prices.colorPrice');
        $boldPrice = config('payment.prices.boldPrice');
        $namePrice = config('payment.prices.namePrice');

        $placePrice = $data['term'] * $placePrice;
        $colorPrice = $data['color'] ? $data['term'] * $colorPrice : 0;
        $boldPrice = $data['bold'] ? $data['term'] * $boldPrice : 0;
        $namesPrice = $countNames > 1 ? $data['term'] * $namePrice * ($countNames - 1) : 0;

        $total = $placePrice + $colorPrice + $boldPrice + $namesPrice;

        return [
            'total' => $total,
            'place' => $placePrice,
            'color' => $colorPrice,
            'bold'  => $boldPrice,
            'names' => $namesPrice,
        ];
    }
}
