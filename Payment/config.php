<?php

return [
    'yookassa_api_url' => 'https://api.yookassa.ru/v3',

    // Задаются в админке: настройки модуля Payment
    'yookassa_shop_id'    => '',
    'yookassa_secret_key' => '',

    'yookassa_currency' => 'RUB',

    // Цены за сутки
    'prices' => [
        'places' => [
            \Modules\Payment\Models\PaidAdvert::TOP_ALL    => 80,
            \Modules\Payment\Models\PaidAdvert::TOP        => 35,
            \Modules\Payment\Models\PaidAdvert::FORUM      => 20,
            \Modules\Payment\Models\PaidAdvert::BOTTOM_ALL => 50,
            \Modules\Payment\Models\PaidAdvert::BOTTOM     => 10,
        ],
        'colorPrice' => 3, // цена за цветной текст
        'boldPrice'  => 3, // цена за жирный текст
        'namePrice'  => 1, // цена за дополнительное название
    ],
];
