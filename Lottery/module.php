<?php

return [
    'name'        => 'Лотерея',
    'description' => 'Пользователи покупают билет и делают ставку от 1 до 100, на следующий день объявляются результаты, победитель получает весь выигрыш плюс все деньги со ставок проигравших. Если победителей более одного, выигрыш делится пропорционально',
    'info'        => <<<'INFO'
Добавьте ссылку перехода на страницу подарков
[code]
<a href="/lottery">Лотерея</a>
[/code]

Размер текущего джэк-пота можно получить с помщью следующего кода
[code]
<?php
$lottery = \Modules\Lottery\Models\Lottery::query()
    ->orderByDesc('day')
    ->first();
?>
{{ plural($lottery->amount, setting('moneyname')) }}
[/code]
INFO,
    'version'     => '1.2',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'jackpot'     => 1000000,  // Сумма выигрыша
    'ticketPrice' => 50,       // Цена билета
    'numberRange' => [1, 100], // Диапазон номеров
];
