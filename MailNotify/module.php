<?php

use Illuminate\Console\Scheduling\Schedule;

return [
    'name'        => 'Почтовая рассылка',
    'description' => 'Письма пользователям: напоминание о непрочитанных сообщениях и поздравление с днём рождения. Нужна настроенная отправка почты',
    'version'     => '1.0.0',
    'requires'    => '14.8.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/mail-notify-settings' => 'mail_notify::mail_notify.settings',
    ],

    'schedule' => function (Schedule $schedule) {
        $schedule->command('add:subscribers')->hourly();
        $schedule->command('add:birthdays')->dailyAt('07:00');
    },
];
