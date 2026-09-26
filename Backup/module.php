<?php

return [
    'name'        => 'Backup',
    'description' => 'Резервные копии базы данных из админ-панели: выбор таблиц, сжатие, скачивание и удаление дампов',
    'version'     => '1.0.4',
    'requires'    => '14.7.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/backups' => 'backup::backup.backup',
    ],
];
