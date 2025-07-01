<?php

use App\Classes\Hook;

// Добавляем ссылку на просмотр
Hook::add('footerColumnMiddle', function ($content) {
    return $content . '<li class="nav-item mb-2">
        <a href="' . route('locations.index') . '">' . __('user_location::locations.title') . '</a>
        </li>' . PHP_EOL;
});
