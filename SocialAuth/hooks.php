<?php

use App\Models\User;
use App\Services\DashboardService;
use App\Support\Hook;
use App\Support\Registry;
use Modules\SocialAuth\Models\Social;

Hook::add('adminSettingsNav', static function () {
    return '<a class="nav-link" href="' . route('social_auth.settings') . '">'
        . __('social_auth::social_auth.settings')
        . '</a>';
});

Hook::add('loginButtons', static function () {
    $providers = [];

    foreach (['google', 'github', 'yandex', 'vk'] as $provider) {
        if (setting('social_' . $provider . '_enabled')) {
            $providers[] = $provider;
        }
    }

    if (empty($providers)) {
        return null;
    }

    return view('social_auth::_buttons', compact('providers'))->render();
});

// Привязки соцсетей живут на странице «Мои данные»: это настройки входа, а не анкета
Hook::add('accountSections', static function (User $user) {
    $availableProviders = array_values(array_filter(
        array_keys(Social::PROVIDERS),
        static fn ($provider) => (bool) setting('social_' . $provider . '_enabled'),
    ));

    if (! $availableProviders) {
        return null;
    }

    $socials = Social::query()
        ->where('user_id', $user->id)
        ->pluck('provider_id', 'provider');

    $list = view('social_auth::_accounts_list', compact('availableProviders', 'socials'))->render();

    return '<div class="section-form mb-3 shadow">'
        . '<div class="section-title"><i class="fa-solid fa-link"></i> ' . __('social_auth::social_auth.linked_accounts') . '</div>'
        . $list
        . '</div>';
});

// Плитка привязок соцсетей в админ-панели (блок админа)
Hook::add('adminBlockAdmin', static function () {
    return '<div class="col">
        <a href="' . route('social_auth.socials') . '" class="app-tile">
            <div class="app-tile-icon" style="background:#0dcaf0"><i class="fas fa-share-nodes"></i></div>
            <div class="app-tile-label">' . __('social_auth::social_auth.socials')
                . '<span class="badge bg-adaptive app-tile-badge">' . Social::query()->count() . '</span></div>
        </a>
    </div>';
});

// Виджет привязок соцсетей на главной админки
Registry::widget('socials', static fn (int $days): array => [
    'label' => __('social_auth::social_auth.widget'),
    'icon'  => 'fas fa-share-nodes',
    'color' => '#0dcaf0',
    'type'  => 'bar',
    ...DashboardService::trend(Social::query(), $days),
]);
