<?php

return [
    'name'        => 'Подарки',
    'description' => 'Подарки для пользователей, бесплатные или за игровую валюту, подарки отображаются в профиле пользователя',
    'info'        => <<<'INFO'
С помощью хуков ссылки будут добавлены автоматически

Добавьте ссылку перехода на страницу подарков
[code]
<a href="/gifts">Подарки</a>
[/code]

Как вывести подарки текущего пользователя в анкету
[code]
$giftsCount = \Modules\Gift\Models\GiftsUser::where('user_id', $user->id)->count(); ?>
<a href="/gifts/{{ $user->login }}">Подарки</a> <span class="badge bg-adaptive">{{ $giftsCount }}</span><br>
[/code]

Как сделать ссылку на отправку подарка
[code]
<a href="/gifts?user={{ $user->login }}">Отправить подарок</a><br>
[/code]
INFO,
    'version'  => '1.3',
    'author'   => 'Vantuz',
    'email'    => 'admin@visavi.net',
    'homepage' => 'https://visavi.net',
    'panel'    => '/admin/gifts',

    'per_page'  => 24, // Кол. подарков на страниц
    'gift_days' => 365, // На какой срок дарить подарок (дней)
];
