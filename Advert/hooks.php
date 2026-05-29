<?php

use App\Classes\Hook;
use App\Classes\Registry;
use App\Models\User;
use Illuminate\Support\Arr;
use Modules\Advert\Models\Advert;

Registry::onDeleteUser(function (User $user): void {
    Advert::query()->where('user_id', $user->id)->delete();
    clearCache('adverts');
    clearCache('adminAdverts');
});

Hook::add('advertTop', static function (): string {
    $html = '';

    $adminAdverts = Advert::statAdminAdverts();
    if ($adminAdverts) {
        $result = Arr::random($adminAdverts);
        $html .= view('advert::adverts/_admin_links', compact('result'));
    }

    $userAdverts = Advert::statUserAdverts();
    $result = '';
    if ($userAdverts) {
        $total = count($userAdverts);
        $show = setting('rekusershow') > $total ? $total : setting('rekusershow');
        $links = Arr::random($userAdverts, $show);
        $result = implode('<br>', $links);
    }

    if ($result || getUser()) {
        $html .= view('advert::adverts/_links', compact('result'));
    }

    return $html;
});

Hook::add('adminSettingsNav', static fn () => '<a class="nav-link" href="' . route('advert.settings') . '">' . __('advert::adverts.settings') . '</a>');

Hook::add('adminBlockEditor', static function () {
    return '<div class="col">
        <a href="/admin/admin-adverts" class="app-tile">
            <div class="app-tile-icon" style="background:#fd7e14"><i class="fas fa-ad"></i></div>
            <div class="app-tile-label">' . __('index.admin_advertising') . '</div>
        </a>
    </div>
    <div class="col">
        <a href="/admin/adverts" class="app-tile">
            <div class="app-tile-icon" style="background:#ffc107"><i class="fas fa-bullhorn"></i></div>
            <div class="app-tile-label">' . __('index.advertising') . '</div>
        </a>
    </div>';
});
