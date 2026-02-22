<?php

return [
    'yookassa_api_url'    => 'https://api.yookassa.ru/v3',
    'yookassa_shop_id'    => env('YOOKASSA_SHOP_ID', ''),
    'yookassa_secret_key' => env('YOOKASSA_SECRET_KEY', ''),
    'yookassa_currency'   => 'RUB',

    // Цены за сутки
    'prices' => [
        'places' => [
            \App\Models\PaidAdvert::TOP_ALL    => 80,
            \App\Models\PaidAdvert::TOP        => 35,
            \App\Models\PaidAdvert::FORUM      => 20,
            \App\Models\PaidAdvert::BOTTOM_ALL => 50,
            \App\Models\PaidAdvert::BOTTOM     => 10,
        ],
        'colorPrice' => 3, // цена за цветной текст
        'boldPrice'  => 3, // цена за жирный текст
        'namePrice'  => 1, // цена за дополнительное название
    ],
];
