<?php

use App\Classes\Hook;

Hook::add('menuActivity', function (string $content) {
    $url = route('notebooks.index');
    $label = __('notebook::notebooks.notebook');

    return $content . '<i class="far fa-circle text-muted"></i> <a href="' . $url . '">' . $label . '</a><br>' . PHP_EOL;
});
