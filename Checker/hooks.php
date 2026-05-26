<?php

use App\Classes\Hook;

Hook::add('adminBlockBoss', static function () {
    return '<i class="far fa-circle text-muted"></i> <a href="/admin/checkers">'
        . __('checker::checker.site_scan')
        . '</a> <span class="badge bg-adaptive">' . statsChecker() . '</span><br>';
});
