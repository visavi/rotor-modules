<?php

use Illuminate\Console\Scheduling\Schedule;

return [
    'name'        => 'Логи посещений',
    'description' => 'Журнал действий администраторов в админ-панели',
    'version'     => '1.0.1',
    'requires'    => '14.3.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    // Планировщик задач
    'schedule' => function (Schedule $schedule) {
        $schedule->command('delete:logs')->daily();
    },
];
