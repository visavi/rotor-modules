<?php

return [
    'name'        => 'Обратная связь',
    'description' => 'Форма обращения к администрации: в личные сообщения владельцу или на email',
    'version'     => '1.0.0',
    'requires'    => '14.9.0',
    'author'      => 'Vantuz',
    'email'       => 'admin@visavi.net',
    'homepage'    => 'https://visavi.net',

    'actions' => [
        '/admin/feedback-settings' => 'feedback::feedback.settings',
    ],
];
