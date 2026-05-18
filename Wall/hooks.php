<?php

use App\Classes\Hook;
use App\Http\Controllers\Admin\SpamController;
use App\Http\Controllers\AjaxController;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Modules\Wall\Models\Wall;

// Cleanup walls on user delete
User::deleting(function ($user) {
    Wall::query()->where('user_id', $user->id)->delete();
    Cache::forget('wall_count_' . $user->id);
});

// Invalidate cache on wall create/delete
Wall::created(function (Wall $wall) {
    Cache::forget('wall_count_' . $wall->user_id);
});

Wall::deleted(function (Wall $wall) {
    Cache::forget('wall_count_' . $wall->user_id);
});

// Register spam type for SpamController admin panel
SpamController::$extraTypes['walls'] = __('wall::walls.wall_posts');

// Register complaint handler for AjaxController
AjaxController::$extraComplaintTypes['walls'] = function (int $id, $page) {
    $model = Wall::query()->find($id);
    return [
        'model' => $model,
        'path'  => $model ? '/walls/' . $model->user->login . '?page=' . $page : null,
    ];
};

// User profile action link
Hook::add('userActionStart', function (string $content, $user) {
    $url   = '/walls/' . $user->login;
    $label = __('wall::walls.wall_posts');
    $count = Cache::remember('wall_count_' . $user->id, 300, fn () => Wall::query()->where('user_id', $user->id)->count());

    return '<i class="fa fa-sticky-note"></i> <a href="' . $url . '">' . $label . '</a> (' . $count . ')<br>' . PHP_EOL . $content;
});

// Admin settings nav link
Hook::add('adminSettingsNav', function (string $content) {
    $url   = route('wall.settings');
    $label = __('wall::walls.settings');

    return $content . '<a class="nav-link" href="' . $url . '">' . $label . '</a>' . PHP_EOL;
});
