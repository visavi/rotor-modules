<?php

use App\Classes\Hook;

// Добавляем ссылку на лотерею на страницу игр
Hook::add('gamesEnd', function ($content) {
    $lottery = \Modules\Lottery\Models\Lottery::query()
        ->orderByDesc('day')
        ->first();

    return $content . '<div class="col-md-4 col-sm-6">
        <div class="section my-3 shadow">
            <i class="fa-solid fa-gamepad fa-5x"></i>
            <a href="/lottery" class="h5">' . __('lottery::lottery.title') . '</a>
            <div class="text-muted">' . __('lottery::lottery.jackpot_amount',
                ['jackpot' => plural($lottery->amount, setting('moneyname'))]
        ) . '</div>
        </div>
    </div>' . PHP_EOL;
});
