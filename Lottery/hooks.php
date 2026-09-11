<?php

use App\Support\Hook;

// Добавляем ссылку на лотерею на страницу игр
Hook::add('gamesEnd', static function () {
    $lottery = \Modules\Lottery\Models\Lottery::query()
        ->orderByDesc('day')
        ->first();

    // Пока не было ни одного тиража, карточке нечего показывать
    if (! $lottery) {
        return '';
    }

    // Классы и цвет те же, что у карточек модуля игр, иначе лотерея выбивается из сетки
    return '<div class="col-md-4 col-sm-6">
        <div class="section game-card shadow" style="--game-color: #f9a825">
            <i class="fa-solid fa-gamepad fa-5x"></i>
            <a href="/lottery" class="h5 stretched-link">' . __('lottery::lottery.title') . '</a>
            <div class="text-muted">' . __(
        'lottery::lottery.jackpot_amount',
        ['jackpot' => plural($lottery->amount, setting('moneyname'))]
    ) . '</div>
        </div>
    </div>';
});
