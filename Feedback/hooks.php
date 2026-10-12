<?php

use App\Support\Hook;

// Ссылка в колонке footer
Hook::add('footerColumnEnd', static fn () => '<li>
    <a class="footer-item" href="' . route('feedback.index') . '">' . __('feedback::feedback.title') . '</a>
</li>');

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', static fn () => '<a class="nav-link" href="' . route('feedback.settings') . '">' . __('feedback::feedback.settings') . '</a>');
