<?php

use App\Support\Hook;

/**
 * Кнопки «Поделиться» под записью
 *
 * Хук вызывается из вьюх модулей: @hook('share', ['url' => ..., 'title' => ...])
 */
Hook::add('share', static function (array $params = []): string {
    $url = $params['url'] ?? request()->url();
    $title = $params['title'] ?? setting('title');

    return view('share::share', ['shareUrl' => $url, 'shareTitle' => $title])->render();
});

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', static fn () => '<a class="nav-link" href="' . route('share.settings') . '">' . __('share::share.settings') . '</a>');
