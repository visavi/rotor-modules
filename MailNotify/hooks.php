<?php

use App\Models\User;
use App\Support\Hook;
use App\Support\Registry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// Согласие на письма в настройках пользователя
Hook::add('settingsFields', static fn (User $user) => view('mail_notify::_settings', compact('user'))->render());

// Подписка хранится ключом отписки, отказ его стирает. Ключ живой подписки
// не меняется: иначе ссылки из уже отправленных писем перестали бы работать.
// Без поля в запросе (API-клиент его не прислал) подписка не трогается
Registry::onSettingsSave(static function (User $user, Request $request): void {
    if (! $request->has('subscribe')) {
        return;
    }

    $subscribe = $request->boolean('subscribe') ? ($user->subscribe ?: Str::random(32)) : null;

    if ($subscribe !== $user->subscribe) {
        $user->update(['subscribe' => $subscribe]);
    }
});

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', static fn () => '<a class="nav-link" href="' . route('mail-notify.settings') . '">' . __('mail_notify::mail_notify.settings') . '</a>');
