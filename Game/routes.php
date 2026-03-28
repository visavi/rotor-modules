<?php

use Illuminate\Support\Facades\Route;
use Modules\Game\Controllers\BanditController;
use Modules\Game\Controllers\BlackjackController;
use Modules\Game\Controllers\DiceController;
use Modules\Game\Controllers\GuessNumberController;
use Modules\Game\Controllers\IndexController;
use Modules\Game\Controllers\SafeController;
use Modules\Game\Controllers\ThimbleController;

/* Игры */
Route::middleware('web')
    ->prefix('games')
    ->group(function () {
        Route::get('/', [IndexController::class, 'index']);

        Route::middleware('check.user')->group(function () {
            Route::get('/dices', [DiceController::class, 'index']);
            Route::get('/dices/go', [DiceController::class, 'go']);

            Route::get('/thimbles', [ThimbleController::class, 'index']);
            Route::get('/thimbles/choice', [ThimbleController::class, 'choice']);
            Route::get('/thimbles/go', [ThimbleController::class, 'go']);

            Route::get('/bandit', [BanditController::class, 'index']);
            Route::get('/bandit/faq', [BanditController::class, 'faq']);
            Route::get('/bandit/go', [BanditController::class, 'go']);

            Route::get('/blackjack', [BlackjackController::class, 'index']);
            Route::get('/blackjack/rules', [BlackjackController::class, 'rules']);
            Route::get('/blackjack/game', [BlackjackController::class, 'game']);
            Route::match(['get', 'post'], '/blackjack/bet', [BlackjackController::class, 'bet']);

            Route::get('/guess', [GuessNumberController::class, 'index']);
            Route::match(['get', 'post'], '/guess/go', [GuessNumberController::class, 'go']);

            Route::get('/safe', [SafeController::class, 'index']);
            Route::match(['get', 'post'], '/safe/go', [SafeController::class, 'go']);
        });
    });
