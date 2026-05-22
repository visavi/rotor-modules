<?php

use App\Classes\Hook;
use App\Classes\Registry;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Modules\Wall\Models\Wall;

Registry::onDeleteUser(function (User $user): void {
    Wall::query()->where('user_id', $user->id)->delete();
    Cache::forget('wall_count_' . $user->id);
});

Registry::complaint('walls', function (int $id, $page) {
    $model = Wall::query()->find($id);

    return [
        'model' => $model,
        'path'  => $model ? '/walls/' . $model->user->login . '?page=' . $page : null,
    ];
});

// User profile action link
Hook::add('userActionStart', function (string $content, $user) {
    $url = '/walls/' . $user->login;
    $label = __('wall::walls.wall_posts');
    $count = Cache::remember('wall_count_' . $user->id, 300, fn () => Wall::query()->where('user_id', $user->id)->count());

    return '<i class="fa fa-sticky-note"></i> <a href="' . $url . '">' . $label . '</a> <span class="badge bg-adaptive">' . $count . '</span><br>' . PHP_EOL . $content;
});

// Admin settings nav link
Hook::add('adminSettingsNav', function (string $content) {
    $url = route('wall.settings');
    $label = __('wall::walls.settings');

    return $content . '<a class="nav-link" href="' . $url . '">' . $label . '</a>' . PHP_EOL;
});
