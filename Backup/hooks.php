<?php

use App\Classes\Hook;

Hook::add('adminBlockBoss', static function () {
    return '<div class="col">
        <a href="/admin/backups" class="app-tile">
            <div class="app-tile-icon" style="background:#198754"><i class="fas fa-database"></i></div>
            <div class="app-tile-label">' . __('backup::backup.backup') . '</div>
        </a>
    </div>';
});
