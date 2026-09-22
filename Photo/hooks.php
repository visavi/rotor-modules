<?php

use App\Models\Comment;
use App\Models\User;
use App\Support\Hook;
use App\Support\Registry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
    // Счётчики живут 5 минут: анкету открывают часто, а два COUNT на каждый показ ни к чему
    [$photos, $comments] = Cache::remember('photo_profile_links_' . $user->id, 300, static fn () => [
        Photo::query()->where('user_id', $user->id)->count(),
        Comment::query()
            ->where('relate_type', Photo::$morphName)
            ->where('user_id', $user->id)
            ->count(),
    ]);

    return view('components.profile.link', [
        'icon'  => 'far fa-image',
        'label' => __('photo::photos.photos'),
        'url'   => route('photos.user-albums', ['user' => $user->login]),
        'count' => $photos,
        'extra' => [
            'label' => __('main.comments'),
            'url'   => route('photos.user-comments', ['user' => $user->login]),
            'count' => $comments,
        ],
    ])->render();
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
