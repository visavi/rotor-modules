<?php

use App\Classes\Hook;

// Добавляем ссылку на просмотр
Hook::add('footerColumnMiddle', static fn () => '<li>
        <a class="footer-item" href="' . route('locations.index') . '">' . __('user_location::locations.title') . '</a>
        </li>');
