<?php

use App\Support\Hook;
use App\Support\Registry;
use Modules\HomeBuilder\Models\Block;

Registry::homepage('home-builder', 'home_builder::home_builder.homepage', static fn () => view('home_builder::homepage', [
    'blocks' => Block::query()->visible()->ordered()->get(),
]));

// Ссылка в блоке «Администрирование» в админке
Hook::add('adminBlockAdmin', static function () {
    return '<div class="col">
        <a href="' . route('admin.home-builder.index') . '" class="app-tile">
            <div class="app-tile-icon" style="background:#20c997"><i class="fas fa-th-large"></i></div>
            <div class="app-tile-label">' . __('home_builder::home_builder.title') . '</div>
        </a>
    </div>';
});
