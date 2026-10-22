<?php

use Illuminate\Console\Scheduling\Schedule;

return [
    'name'        => 'Платежи',
    'description' => 'Приём платежей через YooKassa: заказы, статусы оплаты, покупка платной рекламы и рекламных баннеров, пополнение баланса',
    'info'        => <<<'INFO'
<p>Организация платежей через YooKassa<br>
Возможность продажи цифровых товаров<br>
Прием и обработка платежей</p>

<p>Получите shop_id и secret_key в панели управления yookassa</p>

<p>Укажите эти данные на странице настроек модуля</p>
<pre class="code"><code>/admin/payment-settings</code></pre>

<p>Добавьте URL webhook для HTTP-уведомления в панели управления yookassa</p>
<pre class="code"><code>https://адрес-сайта/payments/webhook</code></pre>

<p>Ссылка на сайте для создания заказов</p>
<pre class="code"><code>&lt;a href="/payments/advert"&gt;Купить рекламу&lt;/a&gt;</code></pre>

<p>Рекламные баннеры (сайдбар и лента главной) включаются ценой места в настройках модуля, там же лимит баннеров на месте</p>
<pre class="code"><code>&lt;a href="/payments/card"&gt;Купить баннер&lt;/a&gt;</code></pre>

<p>Пополнение баланса за деньги включается в настройках модуля, там же курс и минимальная сумма</p>
<pre class="code"><code>&lt;a href="/payments/balance"&gt;Пополнить баланс&lt;/a&gt;</code></pre>

<p>Ссылка в админ-панель для просмотра заказов</p>
<pre class="code"><code>&lt;a href="/admin/orders"&gt;Заказы&lt;/a&gt;</code></pre>

<p>Ссылки будут созданы автоматически с помощью хуков</p>
INFO,
    'version'  => '1.0.5',
    'requires' => '14.9.0',
    'author'   => 'Vantuz',
    'email'    => 'admin@visavi.net',
    'homepage' => 'https://visavi.net',
    'schedule' => function (Schedule $schedule) {
        $schedule->command('payment:clean-card-images')->daily();
    },
    'actions' => [
        '/admin/orders'           => 'payment::payments.orders',
        '/admin/payment-settings' => 'payment::payments.settings',
    ],
];
