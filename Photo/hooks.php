<?php

use App\Classes\Hook;
use App\Classes\Registry;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\Photo\Models\Photo;

Registry::onAdminDeleteUser(function (User $user, Request $request): void {
    if ($request->input('delimages')) {
        $photos = Photo::query()->where('user_id', $user->id)->get();

        foreach ($photos as $photo) {
            $photo->delete();
        }
    }
});

// Добавление чекбокса удаления фотографий на страницу удаления пользователя
Hook::add('adminUserDeleteFields', static fn () => '<div class="form-check">
    <input type="checkbox" class="form-check-input" value="1" name="delimages" id="delimages">
    <label class="form-check-label" for="delimages">' . __('users.photos') . '</label>
</div>');

// Ссылки на фото пользователя в анкете
Hook::add('userProfileLinks', static function ($user) {
    return '<li class="list-inline-item"><b><a href="' . route('photos.user-albums', ['user' => $user->login]) . '">' . __('photo::photos.photos') . '</a></b>'
        . ' (<a href="' . route('photos.user-comments', ['user' => $user->login]) . '">' . __('main.comments') . '</a>)</li>';
});

// Ссылка в боковом меню
Hook::add('sidebarMenu', static function () {
    return '<li>
        <a class="menu-item' . (request()->is('photos*') ? ' active' : '') . '" href="' . route('photos.index') . '">
            <i class="menu-icon far fa-image"></i>
            <span class="menu-label">' . __('photo::photos.photos') . '</span>
            <span class="badge menu-badge">' . statsPhotos() . '</span>
        </a>
    </li>';
}, 14);

// Ссылка в блоке редактора в админке
Hook::add('adminBlockEditor', static function () {
    return '<div class="col">
        <a href="' . route('admin.photos.index') . '" class="app-tile">
            <div class="app-tile-icon" style="background:#e91e63"><i class="far fa-image"></i></div>
            <div class="app-tile-label">' . __('photo::photos.photos') . '<span class="badge bg-adaptive app-tile-badge">' . statsPhotos() . '</span></div>
        </a>
    </div>';
});

// Ссылка в навигации настроек админки
Hook::add('adminSettingsNav', static fn () => '<a class="nav-link" href="' . route('photo.settings') . '">' . __('photo::photos.settings') . '</a>');
