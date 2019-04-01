<?php

use FastRoute\RouteCollector;

/* Подарки */
$r->addGroup('/gifts', static function (RouteCollector $r) {
    $r->get('', [Modules\Gift\Controllers\IndexController::class, 'index']);
    $r->addRoute(['GET', 'POST'], '/send/{id:\d+}', [Modules\Gift\Controllers\IndexController::class, 'send']);
    $r->get('/{login:[\w\-]+}', [Modules\Gift\Controllers\IndexController::class, 'gifts']);
});

$r->addGroup('/admin', static function (RouteCollector $r) {
    $r->addRoute(['GET', 'POST'], '/gifts', [Modules\Gift\Controllers\PanelController::class, 'index']);
    $r->get('/gifts/delete', [Modules\Gift\Controllers\PanelController::class, 'delete']);
});
