<?php

use App\Classes\Hook;
use App\Classes\Registry;
use App\Models\User;
use Modules\Rating\Models\Rating;

// Удаление голосов при удалении пользователя
Registry::onDeleteUser(function (User $user): void {
    Rating::query()
        ->where('user_id', $user->id)
        ->orWhere('recipient_id', $user->id)
        ->delete();
});

// Блок репутации в анкете пользователя
Hook::add('userEnd', static function (User $user) {
    if (getUser()) {
        $html = '<a href="/ratings/' . $user->login . '">' . __('main.reputation') . ': <b>' . formatNum($user->rating) . '</b> (+' . $user->posrating . '/-' . $user->negrating . ')</a><br>';

        if (getUser('login') !== $user->login) {
            $html .= '<a href="/users/' . $user->login . '/rating?vote=plus"><i class="fa fa-arrow-up"></i><span style="color:#0099cc"> ' . __('main.plus') . '</span></a> / '
                . '<a href="/users/' . $user->login . '/rating?vote=minus"><span style="color:#ff0000">' . __('main.minus') . '</span> <i class="fa fa-arrow-down"></i></a><br>';
        }

        return $html;
    }

    return __('main.reputation') . ': <b>' . formatNum($user->rating) . '</b> (+' . $user->posrating . '/-' . $user->negrating . ')<br>';
}, 10);

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', static fn () => '<a class="nav-link" href="' . route('rating.settings') . '">' . __('rating::ratings.settings') . '</a>');
