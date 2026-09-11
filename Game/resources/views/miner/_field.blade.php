@php
    $reveal = $miner['status'] !== null;

    // Мины после проигрыша открываются волной, поэтому каждой нужен свой шаг
    $steps = [];

    if ($reveal) {
        $step = 1;

        foreach ($miner['field'] as $mine) {
            if (! in_array($mine, $miner['opened'], true)) {
                $steps[$mine] = $step++;
            }
        }
    }

    $lastStep = $steps ? max($steps) : 0;
@endphp

<div id="miner-box" style="--last: {{ $lastStep }}">
    <div class="d-flex flex-wrap gap-3 mb-3">
        <div>{{ __('game::games.miner_bet', ['money' => plural($miner['bet'], setting('moneyname'))]) }}</div>
        <div>{{ __('game::games.miner_mines_count', ['count' => $miner['mines']]) }}</div>
        <div>{{ __('game::games.miner_opened_count', ['count' => count($miner['opened'])]) }}</div>
    </div>

    {{-- Клетки — кнопки одной формы: по ссылкам браузер ходит сам при предзагрузке --}}
    <form action="/games/miner/go" method="post" data-ajax data-ajax-replace="#miner-box" data-ajax-swap="outer">
        @csrf

        <div class="miner-field mb-3">
            @for ($cell = 0; $cell < $cells; $cell++)
                @php
                    $isOpen = in_array($cell, $miner['opened'], true);
                    $isMine = in_array($cell, $miner['field'], true);
                @endphp

                @if ($isOpen && $isMine)
                    <span class="btn btn-danger disabled miner-cell @if ($cell === $fresh) miner-boom @endif"><i class="fas fa-bomb"></i></span>
                @elseif ($isOpen)
                    <span class="btn btn-success disabled miner-cell @if ($cell === $fresh) miner-flip @endif"><i class="fas fa-gem"></i></span>
                @elseif ($reveal && $isMine)
                    <span class="btn btn-outline-danger disabled miner-cell miner-reveal" style="--step: {{ $steps[$cell] }}"><i class="fas fa-bomb"></i></span>
                @elseif ($reveal)
                    <span class="btn btn-outline-secondary disabled miner-cell">&nbsp;</span>
                @else
                    <button class="btn btn-secondary miner-cell" name="cell" value="{{ $cell }}">&nbsp;</button>
                @endif
            @endfor
        </div>
    </form>

    @if ($miner['status'] === 'lost')
        <div class="my-3 fw-bold miner-late">
            <span class="text-danger">{{ __('game::games.miner_boom') }}</span><br>
            {{ __('game::games.bj_lost', ['money' => plural($miner['bet'], setting('moneyname'))]) }}
        </div>
    @elseif ($miner['status'] === 'won')
        <div class="my-3 fw-bold miner-late">
            <span class="text-success"><i class="fas fa-trophy"></i> {{ __('game::games.victory') }}</span><br>
            {{ __('game::games.bj_won', ['money' => plural($reward, setting('moneyname'))]) }}
        </div>
    @endif

    @if ($reveal)
        <div class="miner-balance mb-3">
            <span class="miner-balance-old">{{ __('game::games.balance', ['money' => plural($before, setting('moneyname'))]) }}</span>
            <span class="miner-late">{{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}</span>
        </div>

        <div class="miner-late">
            <form action="/games/miner/bet" method="post" class="d-inline" data-ajax data-ajax-replace="#miner-box" data-ajax-swap="outer">
                @csrf
                <input type="hidden" name="bet" value="{{ $miner['bet'] }}">
                <input type="hidden" name="mines" value="{{ $miner['mines'] }}">
                <button class="btn btn-primary">{{ __('game::games.bj_repeat') }}</button>
            </form>
            <br><br>

            <i class="fa fa-coins"></i> <a href="/games/miner">{{ __('game::games.miner_new_game') }}</a>
        </div>
    @else
        <div class="mb-3">{{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}</div>

        @if ($miner['opened'])
            <form action="/games/miner/cash" method="post" class="d-inline" data-ajax data-ajax-replace="#miner-box" data-ajax-swap="outer">
                @csrf
                <button class="btn btn-success">
                    {{ __('game::games.miner_cash', ['money' => plural($reward, setting('moneyname'))]) }}
                </button>
            </form>
            <br><br>
        @endif

        {{ __('game::games.miner_next', ['money' => plural($next, setting('moneyname'))]) }}
    @endif
</div>
