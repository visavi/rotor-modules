<?php

return [
    'name'        => 'Уведомления о сообщениях',
    'description' => 'Фоновая проверка новых личных сообщений: звук, счётчик в шапке, отметка в заголовке вкладки и уведомления браузера',
    'version'     => '1.0.1',
    'requires'    => '14.7.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/notifier-settings' => 'notifier::notifier.settings',
    ],
];
