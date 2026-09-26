<?php

return [
    'name'        => 'Антимат',
    'description' => 'Фильтр нецензурных слов: слова из списка заменяются при выводе текста',
    'version'     => '1.0.1',
    'requires'    => '14.7.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/antimat'          => 'antimat::antimat.title',
        '/admin/antimat-settings' => 'antimat::antimat.settings',
    ],
];
