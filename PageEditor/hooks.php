<?php

use App\Classes\Hook;

// Ссылка в блоке boss в админке
Hook::add('adminBlockBoss', static function () {
    $url = '/admin/files';
    $label = __('page_editor::files.page_editor');

    return '<i class="far fa-circle text-muted"></i> <a href="' . $url . '">' . $label . '</a><br>';
});
