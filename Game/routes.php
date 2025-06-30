<?php

use Illuminate\Support\Facades\Route;

/* Игры */
Route::middleware('web')
    ->prefix('games')
    ->group(function () {
        Route::get('/', [\Modules\Game\Controllers\IndexController::class, 'index']);

        Route::middleware('check.user')->group(function () {
            Route::get('/dices', [\Modules\Game\Controllers\DiceController::class, 'index']);
            Route::get('/dices/go', [\Modules\Game\Controllers\DiceController::class, 'go']);

            Route::get('/thimbles', [\Modules\Game\Controllers\ThimbleController::class, 'index']);
            Route::get('/thimbles/choice', [\Modules\Game\Controllers\ThimbleController::class, 'choice']);
            Route::get('/thimbles/go', [\Modules\Game\Controllers\ThimbleController::class, 'go']);

            Route::get('/bandit', [\Modules\Game\Controllers\BanditController::class, 'index']);
            Route::get('/bandit/faq', [\Modules\Game\Controllers\BanditController::class, 'faq']);
            Route::get('/bandit/go', [\Modules\Game\Controllers\BanditController::class, 'go']);

            Route::get('/blackjack', [\Modules\Game\Controllers\BlackjackController::class, 'index']);
            Route::get('/blackjack/rules', [\Modules\Game\Controllers\BlackjackController::class, 'rules']);
            Route::get('/blackjack/game', [\Modules\Game\Controllers\BlackjackController::class, 'game']);
            Route::match(['get', 'post'], '/blackjack/bet', [\Modules\Game\Controllers\BlackjackController::class, 'bet']);

            Route::get('/guess', [\Modules\Game\Controllers\GuessNumberController::class, 'index']);
            Route::match(['get', 'post'], '/guess/go', [\Modules\Game\Controllers\GuessNumberController::class, 'go']);

            Route::get('/safe', [\Modules\Game\Controllers\SafeController::class, 'index']);
            Route::match(['get', 'post'], '/safe/go', [\Modules\Game\Controllers\SafeController::class, 'go']);
        });
    });
