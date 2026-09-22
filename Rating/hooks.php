<?php

use App\Models\User;
use App\Support\Hook;
use App\Support\Registry;
use Modules\Rating\Models\Rating;

// Удаление голосов при удалении пользователя
Registry::onDeleteUser(function (User $user): void {
    Rating::query()
        ->where('user_id', $user->id)
        ->orWhere('recipient_id', $user->id)
        ->delete();
});

// Плитка репутации в шапке анкеты, рядом с баллами и монетами
Hook::add('userStats', static fn (User $user) => view('components.profile.stat', [
    'value' => ($user->rating > 0 ? '+' : '') . formatShortNum($user->rating),
    'label' => __('main.reputation'),
    'url'   => '/ratings/' . $user->login,
    'class' => $user->rating >= 0 ? 'text-success' : 'text-danger',
])->render());

// Репутация в карточке списка пользователей
Hook::add('userCardStats', static fn (User $user) => '<span data-bs-toggle="tooltip" title="' . __('main.reputation') . '">'
    . '<i class="fas fa-star"></i> ' . formatNum($user->rating)
    . '</span>');

// Блок репутации: итог, доля плюсов и голосование
Hook::add('userSections', static function (User $user) {
    $canVote = getUser() && getUser('login') !== $user->login;

    $rating = view('components.profile.rating', [
        'rating'   => $user->rating,
        'positive' => $user->posrating,
        'negative' => $user->negrating,
        'url'      => '/ratings/' . $user->login,
        'plusUrl'  => $canVote ? '/users/' . $user->login . '/rating?vote=plus' : null,
        'minusUrl' => $canVote ? '/users/' . $user->login . '/rating?vote=minus' : null,
    ])->render();

    return '<div class="section mb-3 shadow">'
        . '<div class="section-title"><i class="fas fa-award"></i> ' . __('main.reputation') . '</div>'
        . '<div class="section-body">' . $rating . '</div>'
        . '</div>';
});

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', static fn () => '<a class="nav-link" href="' . route('rating.settings') . '">' . __('rating::ratings.settings') . '</a>');
