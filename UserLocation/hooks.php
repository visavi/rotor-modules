<?php

use App\Classes\Hook;
use Modules\UserLocation\Models\UserLocation;

// Добавляем ссылку на просмотр
Hook::add('footerColumnMiddle', static fn () => '<li>
        <a class="footer-item" href="' . route('locations.index') . '">' . __('user_location::locations.title') . '</a>
        </li>');

// Последняя страница и IP юзера в админ-карточке (online чистится, IP храним тут)
Hook::add('adminUserCard', static function ($user) {
    if (! $user || ! $user->id) {
        return '';
    }

    $location = UserLocation::query()
        ->where('user_id', $user->id)
        ->orderByDesc('created_at')
        ->first();

    if (! $location) {
        return '';
    }

    return __('user_location::locations.last_page') . ': <a href="' . e($location->path) . '">' . e($location->title) . '</a> '
        . '<small class="text-muted fst-italic">(' . dateFixed($location->created_at) . ')</small><br>'
        . e($location->brow) . ', ' . e($location->ip) . '<br>';
});
