<?php

use App\Classes\Hook;
use App\Classes\Registry;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Modules\Wall\Models\Wall;

Registry::onDeleteUser(function (User $user): void {
    Wall::query()->where('user_id', $user->id)->delete();
    clearCache('wall_count_' . $user->id);
});

Registry::complaint(Wall::$morphName, function (int $id, mixed $page): array {
    $model = Wall::query()->find($id);

    return [
        'model' => $model,
        'path'  => $model ? '/walls/' . $model->user->login . '?page=' . $page : null,
    ];
});

// User profile action link
Hook::add('userActionStart', static function ($user) {
    return '<i class="fa fa-sticky-note"></i> <a href="/walls/' . $user->login . '">' . __('wall::walls.wall_posts') . '</a> <span class="badge bg-adaptive">' . Cache::remember('wall_count_' . $user->id, 300, static fn () => Wall::query()->where('user_id', $user->id)->count()) . '</span><br>';
}, 10);

// Admin settings nav link
Hook::add('adminSettingsNav', static fn () => '<a class="nav-link" href="' . route('wall.settings') . '">' . __('wall::walls.settings') . '</a>');
