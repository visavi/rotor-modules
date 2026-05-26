<?php

use App\Classes\Hook;

Hook::add('adminBlockBoss', static function () {
    return '<div class="col">
        <a href="/admin/checkers" class="app-tile">
            <div class="app-tile-icon" style="background:#dc3545"><i class="fas fa-search"></i></div>
            <div class="app-tile-label">' . __('checker::checker.site_scan') . '<span class="badge bg-adaptive app-tile-badge">' . statsChecker() . '</span></div>
        </a>
    </div>';
});
