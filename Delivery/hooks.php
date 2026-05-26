<?php

use App\Classes\Hook;

Hook::add('adminBlockBoss', static function () {
    return '<div class="col">
        <a href="/admin/delivery" class="app-tile">
            <div class="app-tile-icon" style="background:#0dcaf0"><i class="fas fa-paper-plane"></i></div>
            <div class="app-tile-label">' . __('index.private_mailing') . '</div>
        </a>
    </div>';
});
