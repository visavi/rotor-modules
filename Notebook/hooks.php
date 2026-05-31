<?php

use App\Classes\Hook;
use App\Classes\Registry;
use App\Models\User;
use Modules\Notebook\Models\Notebook;

Hook::add('menuActivity', static fn () => '<i class="far fa-circle text-muted"></i> <a href="' . route('notebooks.index') . '">' . __('notebook::notebooks.notebook') . '</a><br>');

Registry::onDeleteUser(function (User $user): void {
    Notebook::query()->where('user_id', $user->id)->delete();
});
