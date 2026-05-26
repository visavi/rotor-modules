<?php

use App\Classes\Hook;
use App\Classes\Registry;
use Modules\Guestbook\Models\Guestbook;

// Жалобы на сообщения гостевой
Registry::complaint(Guestbook::$morphName, function (int $id, mixed $page): array {
    $model = Guestbook::query()->find($id);
    $path = route('guestbook.index', ['page' => $page], false);

    return compact('model', 'path');
});

// Ссылка в боковом меню (default, nordic, newspaper темы)
Hook::add('sidebarMenu', static function () {
    $active = request()->is('guestbook*') ? ' active' : '';
    $label = __('guestbook::guestbook.guestbook');
    $stats = statsGuestbook();

    return '<li>
        <a class="menu-item' . $active . '" href="' . route('guestbook.index') . '">
            <i class="menu-icon far fa-comment"></i>
            <span class="menu-label">' . $label . '</span>
            <span class="badge menu-badge">' . $stats . '</span>
        </a>
    </li>';
}, 25);

// Ссылка в блоке редактора в админке
Hook::add('adminBlockEditor', static function () {
    return '<div class="col">
        <a href="' . route('admin.guestbook.index') . '" class="app-tile">
            <div class="app-tile-icon" style="background:#20c997"><i class="far fa-comment"></i></div>
            <div class="app-tile-label">' . __('guestbook::guestbook.guestbook') . '<span class="badge bg-adaptive app-tile-badge">' . statsGuestbook() . '</span></div>
        </a>
    </div>';
}, 10);

// Вкладка в настройках администратора
Hook::add('adminSettingsNav', static fn () => '<a class="nav-link" href="' . route('guestbook.settings') . '">' . __('guestbook::guestbook.settings') . '</a>');
