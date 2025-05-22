<?php

return [
    'name'        => 'Платежи',
    'description' => 'Организация платежей на сайте',
    'info'        => <<<'INFO'
Организация платежей через YooKassa
Возможность продажи цифровых товаров
Прием и обработка платежей

В панели управления yookassa необходимо добавить webhook для HTTP-уведомления
[code]
https://адрес-сайта/payments/webhook
[/code]

Ссылка на сайте для создания заказов
[code]
<a href="/payments/advert">Купить рекламу</a>
[/code]

Ссылка в админ-панель для просмотра заказов
[code]
<a href="/admin/orders">Заказы</a>
[/code]

Ссылки будут созданы автоматически с помощью хуков
INFO,
    'version'  => '1.2',
    'author'   => 'Vantuz',
    'email'    => 'admin@visavi.net',
    'homepage' => 'https://visavi.net',
];
