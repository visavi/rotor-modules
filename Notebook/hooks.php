<?php

use App\Models\User;
use App\Support\Hook;
use App\Support\Registry;
use Modules\Notebook\Models\Notebook;

Hook::add('userPersonalEnd', static fn () => view('components.profile.action', [
    'icon'  => 'fa fa-book',
    'label' => __('notebook::notebooks.notebook'),
    'url'   => route('notebooks.index'),
])->render());

Registry::onDeleteUser(function (User $user): void {
    Notebook::query()->where('user_id', $user->id)->delete();
});
