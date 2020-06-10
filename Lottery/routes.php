<?php

use FastRoute\RouteCollector;

/* Лотерея */
$r->addGroup('/lottery', static function (RouteCollector $r) {
    $r->get('', [Modules\Lottery\Controllers\IndexController::class, 'index']);
    $r->post('/buy', [Modules\Lottery\Controllers\IndexController::class, 'buy']);
});
