@php
    // Новые карты открываются по очереди: сначала банкира, затем свои.
    // Задержка живёт в CSS, поэтому ход через ajax анимируется без скрипта
    $steps = [];
    $step = 0;

    foreach (['banker' => 'bankercards', 'user' => 'cards'] as $side => $key) {
        foreach ($blackjack[$key] as $index => $card) {
            $steps[$side][$index] = $index < $shown[$side] ? null : $step++;
        }
    }

    $lastStep = max(0, $step - 1);
@endphp

<div id="bj-table">
    {{-- Пока карты не открыты, баланс показывается на момент до расчёта --}}
    @if ($result)
        <div class="bj-balance">
            <span class="bj-balance-old" style="--step: {{ $lastStep }}">{{ __('game::games.balance', ['money' => plural($before, setting('moneyname'))]) }}</span>
            <span class="bj-late" style="--step: {{ $lastStep }}">{{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}</span>
        </div>
    @else
        {{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}
    @endif
    <br>

    {{-- Как за столом: банкир напротив, свои карты ближе к себе --}}
    <b>{{ __('game::games.bj_banker_cards') }}</b><br>

    @foreach ($blackjack['bankercards'] as $index => $card)
        {{-- Анимируются только карты этого хода, лежавшие раньше остаются на месте --}}
        <img class="bj-card @if ($steps['banker'][$index] !== null) bj-deal @endif" @if ($steps['banker'][$index] !== null) style="--step: {{ $steps['banker'][$index] }}" @endif src="/assets/modules/games/cards/{{ $result ? $card : 0 }}.png" alt="image">
    @endforeach

    @if ($result)
        <br><span class="bj-late" style="--step: {{ $lastStep }}">{{ plural($scores['banker'], __('game::games.bj_points')) }}</span>
    @endif

    <br><br>

    <b>{{ __('game::games.bj_your_cards') }}</b><br>

    @foreach ($blackjack['cards'] as $index => $card)
        <img class="bj-card @if ($steps['user'][$index] !== null) bj-deal @endif" @if ($steps['user'][$index] !== null) style="--step: {{ $steps['user'][$index] }}" @endif src="/assets/modules/games/cards/{{ $card }}.png" alt="image">
    @endforeach

    <br><span class="bj-late" style="--step: {{ $lastStep }}">{{ plural($scores['user'], __('game::games.bj_points')) }}</span><br>

    @if ($result)
        <div class="my-3 fw-bold bj-late" style="--step: {{ $lastStep }}">
            @if ($text)
                {{ $text }}<br>
            @endif

            @if ($result === 'victory')
                <span class="text-success">{{ __('game::games.victory') }}</span><br>
                {{ __('game::games.bj_won', ['money' => plural($amount, setting('moneyname'))]) }}
            @elseif ($result === 'lost')
                <span class="text-danger">{{ __('game::games.lost') }}</span><br>
                {{ __('game::games.bj_lost', ['money' => plural($amount, setting('moneyname'))]) }}
            @else
                {{ __('game::games.draw') }}<br>
                {{ __('game::games.bj_returned', ['money' => plural($amount, setting('moneyname'))]) }}
            @endif
        </div>

        <div class="bj-late" style="--step: {{ $lastStep }}">
            <form action="/games/blackjack/bet" method="post" class="d-inline">
                @csrf
                <input type="hidden" name="bet" value="{{ $blackjack['bet'] }}">
                <button type="submit" class="btn btn-primary">{{ __('game::games.bj_repeat') }}</button>
            </form>
            <br><br>

            <i class="fa fa-coins"></i> <a href="/games/blackjack">{{ __('game::games.bj_new_bet') }}</a><br>
        </div>
    @else
        <div class="my-3">{{ __('game::games.bj_stake', ['money' => plural($blackjack['bet'] * 2, setting('moneyname'))]) }}</div>

        {{-- Ход меняет партию, поэтому уходит post-ом. Ajax подменяет стол целиком,
             и анимация не рвётся перезагрузкой --}}
        <form action="/games/blackjack/game" method="post" class="d-inline" data-ajax data-ajax-replace="#bj-table" data-ajax-swap="outer">
            @csrf
            <input type="hidden" name="case" value="take">
            <button type="submit" class="btn btn-success fw-bold">{{ __('game::games.bj_take_card') }}</button>
        </form>

        {{ __('game::games.bj_or') }}

        <form action="/games/blackjack/game" method="post" class="d-inline" data-ajax data-ajax-replace="#bj-table" data-ajax-swap="outer">
            @csrf
            <input type="hidden" name="case" value="end">
            <button type="submit" class="btn btn-danger fw-bold">{{ __('game::games.bj_open') }}</button>
        </form>
        <br><br>
    @endif
</div>
