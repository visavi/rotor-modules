<?php

use FastRoute\RouteCollector;

/* Подарки */
$r->addGroup('/gifts', static function (RouteCollector $r) {
    $r->get('', [App\Modules\Gift\Controllers\IndexController::class, 'index']);
    $r->addRoute(['GET', 'POST'], '/send/{id:\d+}', [App\Modules\Gift\Controllers\IndexController::class, 'send']);
    $r->get('/{login:[\w\-]+}', [App\Modules\Gift\Controllers\IndexController::class, 'gifts']);
});

$r->addGroup('/admin', static function (RouteCollector $r) {
    $r->addRoute(['GET', 'POST'], '/gifts', [App\Modules\Gift\Controllers\PanelController::class, 'index']);
    $r->get('/gifts/delete', [App\Modules\Gift\Controllers\PanelController::class, 'delete']);
});
