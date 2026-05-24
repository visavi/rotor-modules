<?php

use App\Classes\Hook;

Hook::add('adminBlockBoss', static function () {
    return '<i class="far fa-circle text-muted"></i> <a href="/admin/backups">' . __('backup::backup.backup') . '</a><br>';
});
