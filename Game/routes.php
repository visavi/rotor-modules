<?php

use FastRoute\RouteCollector;

/* Игры */
$r->addGroup('/games', static function (RouteCollector $r) {
    $r->get('', [Modules\Game\Controllers\IndexController::class, 'index']);

    $r->get('/dices', [Modules\Game\Controllers\DiceController::class, 'index']);
    $r->get('/dices/go', [Modules\Game\Controllers\DiceController::class, 'go']);

    $r->get('/thimbles', [Modules\Game\Controllers\ThimbleController::class, 'index']);
    $r->get('/thimbles/choice', [Modules\Game\Controllers\ThimbleController::class, 'choice']);
    $r->get('/thimbles/go', [Modules\Game\Controllers\ThimbleController::class, 'go']);

    $r->get('/bandit', [Modules\Game\Controllers\BanditController::class, 'index']);
    $r->get('/bandit/faq', [Modules\Game\Controllers\BanditController::class, 'faq']);
    $r->get('/bandit/go', [Modules\Game\Controllers\BanditController::class, 'go']);

    $r->get('/blackjack', [Modules\Game\Controllers\BlackjackController::class, 'index']);
    $r->get('/blackjack/rules', [Modules\Game\Controllers\BlackjackController::class, 'rules']);
    $r->get('/blackjack/game', [Modules\Game\Controllers\BlackjackController::class, 'game']);
    $r->addRoute(['GET', 'POST'], '/blackjack/bet', [Modules\Game\Controllers\BlackjackController::class, 'bet']);

    $r->get('/guess', [Modules\Game\Controllers\GuessNumberController::class, 'index']);
    $r->addRoute(['GET', 'POST'], '/guess/go', [Modules\Game\Controllers\GuessNumberController::class, 'go']);

    $r->get('/safe', [Modules\Game\Controllers\SafeController::class, 'index']);
    $r->addRoute(['GET', 'POST'], '/safe/go', [Modules\Game\Controllers\SafeController::class, 'go']);
});
