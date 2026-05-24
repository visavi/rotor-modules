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
    $label = __('index.guestbook');
    $stats = statsGuestbook();

    return '<li>
        <a class="menu-item' . $active . '" href="' . route('guestbook.index') . '">
            <i class="menu-icon far fa-comment"></i>
            <span class="menu-label">' . $label . '</span>
            <span class="badge menu-badge">' . $stats . '</span>
        </a>
    </li>';
}, 5);

// Ссылка в блоке редактора в админке
Hook::add('adminBlockEditor', static function () {
    $label = __('index.guestbook');
    $stats = statsGuestbook();

    return '<i class="far fa-circle text-muted"></i> <a href="' . route('admin.guestbook.index') . '">' . $label . '</a> <span class="badge bg-adaptive">' . $stats . '</span><br>';
}, 10);

// Вкладка в настройках администратора
Hook::add('adminSettingsNav', static fn () => '<a class="nav-link" href="' . route('guestbook.settings') . '">' . __('guestbook::guestbook.settings') . '</a>');
