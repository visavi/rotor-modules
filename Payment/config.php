<?php

return [
    'yookassa_api_url'  => 'https://api.yookassa.ru/v3',
    'yookassa_currency' => 'RUB',

    // Сколько секунд после создания заказа показывать ссылку «Продолжить оплату»
    // (страница оплаты ЮКассы живёт ограниченное время)
    'confirmation_ttl' => 600,

    // Пополнение баланса: сумма в форме по умолчанию
    // и предел одного платежа, в рублях
    'balance_default_amount' => 100,
    'balance_max_amount'     => 100000,
];
