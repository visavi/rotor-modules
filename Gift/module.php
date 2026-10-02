<?php

return [
    'name'        => 'Подарки',
    'description' => 'Подарки пользователям, бесплатные или за игровую валюту, с показом в профиле',
    'info'        => <<<'INFO'
<p>С помощью хуков ссылки будут добавлены автоматически</p>

<p>Добавьте ссылку перехода на страницу подарков</p>
<pre class="code"><code>&lt;a href="/gifts"&gt;Подарки&lt;/a&gt;</code></pre>

<p>Как вывести подарки текущего пользователя в анкету</p>
<pre class="code"><code>$giftsCount = \Modules\Gift\Models\GiftsUser::where('user_id', $user-&gt;id)-&gt;count();
&lt;a href="/gifts/{{ $user-&gt;login }}"&gt;Подарки&lt;/a&gt; &lt;span class="badge bg-adaptive"&gt;{{ $giftsCount }}&lt;/span&gt;</code></pre>

<p>Как сделать ссылку на отправку подарка</p>
<pre class="code"><code>&lt;a href="/gifts?user={{ $user-&gt;login }}"&gt;Отправить подарок&lt;/a&gt;</code></pre>
INFO,
    'version'  => '1.3.0',
    'requires' => '14.8.0',
    'author'   => 'Vantuz',
    'email'    => 'admin@visavi.net',
    'homepage' => 'https://visavi.net',
    'actions'  => [
        '/admin/gifts'         => 'gift::gifts.title',
        '/admin/gift-settings' => 'gift::gifts.settings',
    ],
];
