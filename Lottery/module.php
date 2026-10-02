<?php

use Illuminate\Console\Scheduling\Schedule;

return [
    'name'        => 'Лотерея',
    'description' => 'Ежедневная лотерея: пользователи покупают билет со ставкой, победитель забирает банк, при нескольких — делят пропорционально',
    'info'        => <<<'INFO'
<p>Ссылка на лотерею будет автоматически добавлена на страницу игр через хуки<br>
Или добавьте ссылку перехода на страницу лотереи самостоятельно</p>
<pre class="code"><code>&lt;a href="/lottery"&gt;Лотерея&lt;/a&gt;</code></pre>

<p>Размер текущего джек-пота можно получить с помощью следующего кода</p>
<pre class="code"><code>&lt;?php
$lottery = \Modules\Lottery\Models\Lottery::query()
    -&gt;orderByDesc('day')
    -&gt;first();
?&gt;
{{ plural($lottery-&gt;amount, setting('moneyname')) }}</code></pre>
INFO,
    'version'  => '1.0.4',
    'requires' => '14.7.0',
    'author'   => 'Vantuz',
    'email'    => 'admin@visavi.net',
    'homepage' => 'https://visavi.net',
    'actions'  => [
        '/admin/lottery-settings' => 'lottery::lottery.settings',
    ],

    // Розыгрыш раз в сутки. Планировщик включен не везде, поэтому заход
    // на страницу лотереи тоже разыгрывает тираж, если время пришло
    'schedule' => function (Schedule $schedule) {
        $schedule->command('lottery:draw')->dailyAt('00:05');
    },
];
