<?php

use App\Classes\Hook;
use App\Classes\Restatement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Modules\Photo\Models\Photo;

Restatement::register('photos', function () {
    DB::update('update photos set count_comments = (select count(*) from comments where relate_type = "' . Photo::$morphName . '" and photos.id = comments.relate_id)');
});

// Ссылки на фото пользователя в анкете
Hook::add('userProfileLinks', function (string $content, $user) {
    if (! Route::has('photos.index')) {
        return $content;
    }

    $link = ' / <b><a href="' . route('photos.user-albums', ['user' => $user->login]) . '">' . __('index.photos') . '</a></b>'
        . ' (<a href="' . route('photos.user-comments', ['user' => $user->login]) . '">' . __('main.comments') . '</a>)';

    return $content . $link;
});

// Ссылка в боковом меню
Hook::add('sidebarMenuEnd', function (string $content) {
    $url = route('photos.index');
    $active = request()->is('photos*') ? ' active' : '';
    $label = __('index.photos');
    $stats = statsPhotos();

    return $content . '<li>
        <a class="menu-item' . $active . '" href="' . $url . '">
            <i class="menu-icon far fa-image"></i>
            <span class="menu-label">' . $label . '</span>
            <span class="badge menu-badge">' . $stats . '</span>
        </a>
    </li>' . PHP_EOL;
}, 10);

// Ссылка в блоке редактора в админке
Hook::add('adminBlockEditor', function (string $content) {
    $url = route('admin.photos.index');
    $label = __('index.photos');
    $stats = statsPhotos();

    return $content
        . '<i class="far fa-circle text-muted"></i> <a href="' . $url . '">' . $label . '</a> <span class="badge bg-adaptive">' . $stats . '</span><br>' . PHP_EOL;
});

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', function (string $content) {
    $url = route('photo.settings');
    $label = __('photo::photos.settings');

    return $content . '<a class="nav-link" href="' . $url . '">' . $label . '</a>' . PHP_EOL;
});
