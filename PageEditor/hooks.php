<?php

use App\Classes\Hook;

// Ссылка в блоке boss в админке
Hook::add('adminBlockBoss', static function () {
    return '<div class="col">
        <a href="/admin/files" class="app-tile">
            <div class="app-tile-icon" style="background:#a91bf3"><i class="fas fa-file-code"></i></div>
            <div class="app-tile-label">' . __('page_editor::files.page_editor') . '</div>
        </a>
    </div>';
});
