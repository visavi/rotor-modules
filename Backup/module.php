<?php

return [
    'name'        => 'Backup',
    'description' => 'Резервное копирование базы данных',
    'version'     => '1.0.0',
    'requires'    => '14.0.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/backups' => __('backup::backup.backup'),
    ],
];
