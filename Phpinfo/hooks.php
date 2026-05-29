<?php

use App\Classes\Hook;

// Плитка PHP-информации в админ-панели (блок администратора)
Hook::add('adminBlockAdmin', static function () {
    return '<div class="col">
        <a href="' . route('admin.phpinfo') . '" class="app-tile">
            <div class="app-tile-icon" style="background:#6c757d"><i class="fas fa-info-circle"></i></div>
            <div class="app-tile-label">' . __('phpinfo::phpinfo.title') . '<span class="badge bg-adaptive app-tile-badge">' . parseVersion(PHP_VERSION) . '</span></div>
        </a>
    </div>';
});
