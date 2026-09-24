<?php

use App\Services\DashboardService;
use App\Support\Hook;
use App\Support\Registry;
use Modules\Gift\Models\GiftsUser;

// Добавляем ссылку на подарки в меню сайта
Hook::add('sidebarMenu', static fn () => '<li>
        <a class="menu-item' . (request()->is('gifts*') ? ' active' : '') . '" href="/gifts">
            <i class="menu-icon fas fa-gift"></i>
            <span class="menu-label">Подарки</span>
        </a>
    </li>');

// Добавляем ссылку на мои подарки в личный кабинет
Hook::add('userActionMiddle', static function ($user) {
    return view('components.profile.action', [
        'icon'  => 'fas fa-gift',
        'label' => 'Подарки',
        'url'   => '/gifts/' . $user->login,
        'badge' => GiftsUser::query()->where('user_id', $user->id)->count(),
    ])->render();
});

// Добавляем ссылку на отправку подарка пользователю
Hook::add('userNotPersonalStart', static fn ($user) => view('components.profile.action', [
    'icon'  => 'fas fa-gift',
    'label' => __('gift::gifts.send_gift'),
    'url'   => '/gifts?user=' . $user->login,
])->render());

// Подарок можно отправить прямо из переписки
Hook::add('messageActions', static fn ($user) => view('components.profile.action', [
    'icon'  => 'fas fa-gift',
    'label' => __('gift::gifts.send_gift'),
    'url'   => '/gifts?user=' . $user->login,
])->render());

// Виджет подарков на главной админки
Registry::widget('gifts', static fn (int $days): array => [
    'label' => __('gift::gifts.title'),
    'icon'  => 'fas fa-gift',
    'color' => '#d63384',
    'type'  => 'bar',
    'url'   => '/gifts',
    ...DashboardService::trend(GiftsUser::query(), $days),
]);
