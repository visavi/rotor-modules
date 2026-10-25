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
        Route::get('/', [IndexController::class, 'index'])->name('games.index');

        Route::middleware('check.user')->group(function () {
            Route::get('/dices', [DiceController::class, 'index'])->name('games.dices');
            Route::post('/dices/roll', [DiceController::class, 'roll'])->name('games.dices.roll');

            Route::get('/thimbles', [ThimbleController::class, 'index'])->name('games.thimbles');
            Route::post('/thimbles/go', [ThimbleController::class, 'go'])->name('games.thimbles.go');

            Route::get('/bandit', [BanditController::class, 'index'])->name('games.bandit');
            Route::get('/bandit/faq', [BanditController::class, 'faq'])->name('games.bandit.faq');
            Route::post('/bandit/spin', [BanditController::class, 'spin'])->name('games.bandit.spin');

            Route::get('/blackjack', [BlackjackController::class, 'index'])->name('games.blackjack');
            Route::get('/blackjack/rules', [BlackjackController::class, 'rules'])->name('games.blackjack.rules');
            Route::get('/blackjack/game', [BlackjackController::class, 'game'])->name('games.blackjack.game');
            Route::post('/blackjack/game', [BlackjackController::class, 'move']);
            Route::post('/blackjack/bet', [BlackjackController::class, 'bet'])->name('games.blackjack.bet');

            Route::get('/guess', [GuessNumberController::class, 'index'])->name('games.guess');
            Route::post('/guess/go', [GuessNumberController::class, 'go'])->name('games.guess.go');
            Route::post('/guess/reset', [GuessNumberController::class, 'reset'])->name('games.guess.reset');

            Route::get('/miner', [MinerController::class, 'index'])->name('games.miner');
            Route::get('/miner/game', [MinerController::class, 'game'])->name('games.miner.game');
            Route::post('/miner/bet', [MinerController::class, 'bet'])->name('games.miner.bet');
            Route::post('/miner/go', [MinerController::class, 'go'])->name('games.miner.go');
            Route::post('/miner/cash', [MinerController::class, 'cash'])->name('games.miner.cash');

            Route::get('/baccarat', [BaccaratController::class, 'index'])->name('games.baccarat');
            Route::post('/baccarat/deal', [BaccaratController::class, 'deal'])->name('games.baccarat.deal');
            Route::post('/baccarat/decide', [BaccaratController::class, 'decide'])->name('games.baccarat.decide');

            Route::get('/highlow', [HighLowController::class, 'index'])->name('games.highlow');
            Route::get('/highlow/rules', [HighLowController::class, 'rules'])->name('games.highlow.rules');
            Route::post('/highlow/bet', [HighLowController::class, 'bet'])->name('games.highlow.bet');
            Route::post('/highlow/move', [HighLowController::class, 'move'])->name('games.highlow.move');
            Route::post('/highlow/cash', [HighLowController::class, 'cash'])->name('games.highlow.cash');

            Route::get('/keno', [KenoController::class, 'index'])->name('games.keno');
            Route::get('/keno/rules', [KenoController::class, 'rules'])->name('games.keno.rules');
            Route::post('/keno/play', [KenoController::class, 'play'])->name('games.keno.play');

            Route::get('/roulette', [RouletteController::class, 'index'])->name('games.roulette');
            Route::post('/roulette/spin', [RouletteController::class, 'spin'])->name('games.roulette.spin');

            Route::get('/safe', [SafeController::class, 'index'])->name('games.safe');
            Route::post('/safe/go', [SafeController::class, 'go'])->name('games.safe.go');
        });
    });
