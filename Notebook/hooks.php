<?php

use App\Models\User;
use App\Support\Hook;
use App\Support\Registry;
use Modules\Notebook\Models\Notebook;

Hook::add('userPersonalEnd', static fn () => '<i class="fa fa-book"></i> <a href="' . route('notebooks.index') . '">' . __('notebook::notebooks.notebook') . '</a><br>');

Registry::onDeleteUser(function (User $user): void {
    Notebook::query()->where('user_id', $user->id)->delete();
});
