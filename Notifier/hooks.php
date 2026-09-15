<?php

use App\Support\Hook;
use Modules\Notifier\Support\Notifier;

// Скрипт фоновой проверки сообщений
Hook::add('footer', static function (): ?string {
    if (! setting('notifier_active')) {
        return null;
    }

    $user = getUser();

    if (! $user || ! $user->isActive()) {
        return null;
    }

    return view('notifier::_notifier', [
        'config'  => Notifier::config($user),
        'version' => Notifier::assetVersion(),
    ])->render();
});

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', static fn () => '<a class="nav-link" href="' . route('notifier.settings') . '">' . __('notifier::notifier.settings') . '</a>');
