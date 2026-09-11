<?php

use Illuminate\Support\Facades\Route;
use Modules\Game\Http\Controllers\BaccaratController;
use Modules\Game\Http\Controllers\BanditController;
use Modules\Game\Http\Controllers\BlackjackController;
use Modules\Game\Http\Controllers\DiceController;
use Modules\Game\Http\Controllers\GuessNumberController;
use Modules\Game\Http\Controllers\HighLowController;
use Modules\Game\Http\Controllers\IndexController;
use Modules\Game\Http\Controllers\KenoController;
use Modules\Game\Http\Controllers\MinerController;
use Modules\Game\Http\Controllers\RouletteController;
use Modules\Game\Http\Controllers\SafeController;
use Modules\Game\Http\Controllers\ThimbleController;

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
            Route::post('/blackjack/game', [BlackjackController::class, 'move']);
            Route::post('/blackjack/bet', [BlackjackController::class, 'bet']);

            Route::get('/guess', [GuessNumberController::class, 'index']);
            Route::match(['get', 'post'], '/guess/go', [GuessNumberController::class, 'go']);

            Route::get('/miner', [MinerController::class, 'index']);
            Route::get('/miner/game', [MinerController::class, 'game']);
            Route::post('/miner/bet', [MinerController::class, 'bet']);
            Route::post('/miner/go', [MinerController::class, 'go']);
            Route::post('/miner/cash', [MinerController::class, 'cash']);

            Route::get('/baccarat', [BaccaratController::class, 'index']);
            Route::post('/baccarat/deal', [BaccaratController::class, 'deal']);
            Route::post('/baccarat/decide', [BaccaratController::class, 'decide']);

            Route::get('/highlow', [HighLowController::class, 'index']);
            Route::get('/highlow/rules', [HighLowController::class, 'rules']);
            Route::post('/highlow/bet', [HighLowController::class, 'bet']);
            Route::post('/highlow/move', [HighLowController::class, 'move']);
            Route::post('/highlow/cash', [HighLowController::class, 'cash']);

            Route::get('/keno', [KenoController::class, 'index']);
            Route::get('/keno/rules', [KenoController::class, 'rules']);
            Route::post('/keno/play', [KenoController::class, 'play']);

            Route::get('/roulette', [RouletteController::class, 'index']);
            Route::post('/roulette/spin', [RouletteController::class, 'spin']);

            Route::get('/safe', [SafeController::class, 'index']);
            Route::match(['get', 'post'], '/safe/go', [SafeController::class, 'go']);
        });
    });
