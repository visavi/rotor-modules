<?php

return [
    'name'        => 'Платежи',
    'description' => 'Организация платежей на сайте',
    'info'        => <<<'INFO'
Организация платежей через YooKassa
Возможность продажи цифровых товаров
Прием и обработка платежей

Получите shop_id и secret_key в панели управления yookassa

Пропишите эти данные в файле .env
YOOKASSA_SHOP_ID=
YOOKASSA_SECRET_KEY=

Добавьте URL webhook для HTTP-уведомления в панели управления yookassa
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
