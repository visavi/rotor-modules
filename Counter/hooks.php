<?php

use App\Classes\Hook;
use App\Classes\Registry;
use Modules\Counter\Services\CounterStatistic;

// Учёт хостов и хитов при сохранении статистики
Registry::onSaveStatistic(static function (bool $newHost, int $hits): void {
    (new CounterStatistic())->save($newHost, $hits);
});

// Счётчик посещений в футере
Hook::add('counter', static function (): ?string {
    $incount = setting('incount');

    if ($incount <= 0) {
        return null;
    }

    $counter = statsCounter();
    $online = statsOnline()[2];

    $cols = [
        1 => ['lbl1' => __('counter::counters.hosts'), 'val1' => $counter['dayhosts'], 'lbl2' => __('counter::counters.hosts_total'), 'val2' => $counter['allhosts']],
        2 => ['lbl1' => __('counter::counters.hits'), 'val1' => $counter['dayhits'], 'lbl2' => __('counter::counters.hits_total'), 'val2' => $counter['allhits']],
        3 => ['lbl1' => __('counter::counters.hosts'), 'val1' => $counter['dayhosts'], 'lbl2' => __('counter::counters.hits'), 'val2' => $counter['dayhits']],
        4 => ['lbl1' => __('counter::counters.hosts_total'), 'val1' => $counter['allhosts'], 'lbl2' => __('counter::counters.hits_total'), 'val2' => $counter['allhits']],
    ];

    $col = $cols[$incount] ?? $cols[3];

    $barColors = ['Mon' => '#0d6efd', 'Tue' => '#6610f2', 'Wed' => '#198754', 'Thu' => '#fd7e14', 'Fri' => '#dc3545', 'Sat' => '#0dcaf0', 'Sun' => '#ffc107'];

    $week = statsWeek();

    $maxHosts = $week->max('hosts') ?: 1;
    $bars = [];
    for ($i = 6; $i >= 0; $i--) {
        $ts = now()->subDays($i)->timestamp;
        $date = date('Y-m-d 00:00:00', $ts);
        $dow = date('D', $ts);
        $hosts = $week->get($date)?->hosts;
        $bars[] = [
            'h' => max(7, (int) round($hosts / $maxHosts * 20)),
            'c' => $barColors[$dow] ?? '#0d6efd',
            'l' => __('main.' . strtolower(substr($dow, 0, 2))),
        ];
    }

    return view('counter::_counter', ['online' => $online, 'bars' => $bars, ...$col])->render();
});

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', static fn () => '<a class="nav-link" href="' . route('counter.settings') . '">' . __('counter::counters.settings') . '</a>');
