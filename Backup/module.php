<?php

return [
    'name'        => 'Backup',
    'description' => 'Резервное копирование базы данных',
    'version'     => '1.0.2',
    'requires'    => '14.3.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/backups' => __('backup::backup.backup'),
    ],
];
