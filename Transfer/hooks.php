<?php

use App\Models\User;
use App\Services\DashboardService;
use App\Support\Hook;
use App\Support\Registry;
use Modules\Transfer\Models\Transfer;

// Ссылка на перевод денег в анкете пользователя
Hook::add('userNotPersonalStart', static function ($user) {
    return view('components.profile.action', [
        'icon'  => 'fas fa-coins',
        'label' => __('transfer::transfers.money_transfer'),
        'url'   => route('transfers.index', ['user' => $user->login]),
    ])->render();
});

// Ссылка на операции пользователя в анкете (для модератора)
Hook::add('userNotPersonalEnd', static function ($user) {
    if (! isAdmin('moder')) {
        return '';
    }

    return view('components.profile.action', [
        'icon'  => 'fa-solid fa-money-bill-transfer',
        'label' => __('transfer::transfers.cash_transactions'),
        'url'   => route('admin.transfers.view', ['user' => $user->login]),
    ])->render();
});

// Перевод денег доступен прямо из переписки
Hook::add('messageActions', static fn ($user) => view('components.profile.action', [
    'icon'  => 'fas fa-coins',
    'label' => __('transfer::transfers.money_transfer'),
    'url'   => route('transfers.index', ['user' => $user->login]),
])->render());

// Плитка денежных операций в админ-панели (блок модератора)
Hook::add('adminBlockModer', static function () {
    return '<div class="col">
        <a href="' . route('admin.transfers.index') . '" class="app-tile">
            <div class="app-tile-icon" style="background:#198754"><i class="fas fa-exchange-alt"></i></div>
            <div class="app-tile-label">' . __('transfer::transfers.cash_transactions') . '</div>
        </a>
    </div>';
});

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', static fn () => '<a class="nav-link" href="' . route('transfer.settings') . '">' . __('transfer::transfers.settings') . '</a>');

// Виджет денежных переводов на главной админки
Registry::widget('transfers', static fn (int $days): array => [
    'label' => __('transfer::transfers.widget'),
    'icon'  => 'fas fa-coins',
    'color' => '#ffc107',
    'type'  => 'bar',
    'url'   => route('admin.transfers.index'),
    'level' => User::MODER,
    ...DashboardService::trend(Transfer::query(), $days),
]);
