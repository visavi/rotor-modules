<?php

return [
    'name'        => 'Платежи',
    'description' => 'Организация платежей на сайте',
    'info'        => <<<'INFO'
<p>Организация платежей через YooKassa<br>
Возможность продажи цифровых товаров<br>
Прием и обработка платежей</p>

<p>Получите shop_id и secret_key в панели управления yookassa</p>

<p>Пропишите эти данные в файле .env</p>
<pre class="code"><code>YOOKASSA_SHOP_ID=Ваш shop_id
YOOKASSA_SECRET_KEY=Ваш secret_key</code></pre>

<p>Добавьте URL webhook для HTTP-уведомления в панели управления yookassa</p>
<pre class="code"><code>https://адрес-сайта/payments/webhook</code></pre>

<p>Ссылка на сайте для создания заказов</p>
<pre class="code"><code>&lt;a href="/payments/advert"&gt;Купить рекламу&lt;/a&gt;</code></pre>

<p>Ссылка в админ-панель для просмотра заказов</p>
<pre class="code"><code>&lt;a href="/admin/orders"&gt;Заказы&lt;/a&gt;</code></pre>

<p>Ссылки будут созданы автоматически с помощью хуков</p>
INFO,
    'version'  => '1.0.0',
    'requires' => '14.0.0',
    'author'   => 'Vantuz',
    'email'    => 'admin@visavi.net',
    'homepage' => 'https://visavi.net',
    'actions'  => [
        '/admin/orders'           => __('payment::payments.orders'),
        '/admin/payment-settings' => __('payment::payments.settings'),
    ],
];
