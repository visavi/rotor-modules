<?php

use App\Classes\Hook;

// Ссылка на перевод денег в анкете пользователя
Hook::add('userNotPersonalStart', static function ($user) {
    return '<i class="fas fa-coins"></i> <a href="' . route('transfers.index', ['user' => $user->login]) . '">' . __('transfer::transfers.money_transfer') . '</a><br>';
});

// Ссылка на операции пользователя в анкете (для модератора)
Hook::add('userNotPersonalEnd', static function ($user) {
    if (! isAdmin('moder')) {
        return '';
    }

    return '<i class="fa-solid fa-money-bill-transfer"></i> <a href="' . route('admin.transfers.view', ['user' => $user->login]) . '">' . __('transfer::transfers.cash_transactions') . '</a><br>';
});

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
