<?php

declare(strict_types=1);

namespace Modules\Payment\Services;

use Modules\Payment\Models\Order;
use Modules\Payment\Models\PaidAdvert;

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
     * Возвращает цены за сутки размещения
     */
    public function getPrices(): array
    {
        $places = [];
        foreach (PaidAdvert::PLACES as $place) {
            $places[$place] = setting('payment_price_' . $place, 0);
        }

        return [
            'places' => $places,
            'color'  => setting('payment_price_color', 0),
            'bold'   => setting('payment_price_bold', 0),
            'name'   => setting('payment_price_name', 0),
        ];
    }

    /**
     * Calculate
     */
    public function calculateAdvert(array $data): array
    {
        $countNames = count($data['names']);
        $prices = $this->getPrices();

        $placePrice = $prices['places'][$data['place']] ?? 0;
        $colorPrice = $prices['color'];
        $boldPrice = $prices['bold'];
        $namePrice = $prices['name'];

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
