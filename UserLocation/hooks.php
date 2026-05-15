<?php

use App\Classes\Hook;

// Добавляем ссылку на просмотр
Hook::add('footerColumnMiddle', function ($content) {
    return $content . '<li>
        <a class="footer-item" href="' . route('locations.index') . '">' . __('user_location::locations.title') . '</a>
        </li>' . PHP_EOL;
});
