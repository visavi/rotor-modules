<?php

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

Hook::add('userPersonalEnd', static function () {
    $hasEnabled = false;
    foreach (['google', 'github', 'yandex', 'vk'] as $provider) {
        if (setting('social_' . $provider . '_enabled')) {
            $hasEnabled = true;
            break;
        }
    }

    if (! $hasEnabled) {
        return null;
    }

    return '<i class="fa-solid fa-link"></i> <a href="' . route('social.accounts') . '">'
        . __('social_auth::social_auth.linked_accounts')
        . '</a><br>';
});

// Виджет привязок соцсетей на главной админки
Registry::widget('socials', static fn (int $days): array => [
    'label' => __('social_auth::social_auth.widget'),
    'icon'  => 'fas fa-share-nodes',
    'color' => '#0dcaf0',
    'type'  => 'bar',
    ...DashboardService::trend(Social::query(), $days),
]);
