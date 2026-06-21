<?php

return [
    'name'        => 'Олбанскей йазыг',
    'description' => 'Полноценный модуль-язык: публикует олбанский (ol). Олбанский перевод + флаг с медведом.',
    'info'        => 'Полный перевод всех языковых файлов на основе русского в стиле «языка падонкафф»',
    'version'     => '1.0.0',
    'requires'    => '14.0.3',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'publish' => [
        'stubs/lang/ol'      => 'resources/lang/ol',
        'stubs/flags/ol.svg' => 'public/assets/flags/ol.svg',

        // Подмешиваем перевод в модуль Форума — копируется только если он есть на диске
        'stubs/forum/lang/ol' => 'modules/Forum/resources/lang/ol',
    ],
];
