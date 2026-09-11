@php
    ['field' => $field, 'draw' => $draw, 'picks' => $picks_limit] = $limits;

    // Шары загораются по очереди: номер шара задаёт задержку, всё остальное делает CSS
    $steps = $game ? array_flip($game['drawn']) : [];
    $picks = $game['picks'] ?? array_map('intval', (array) old('numbers', []));
    $bet = $game['bet'] ?? old('bet');
@endphp

<div id="keno-box">
    <form action="/games/keno/play" method="post" data-ajax data-ajax-replace="#keno-box" data-ajax-swap="outer">
        @csrf

        <div class="keno-field mb-3">
            @for ($number = 1; $number <= $field; $number++)
                <label class="keno-cell @isset($steps[$number]) keno-drawn @endisset" @isset($steps[$number]) style="--step: {{ $steps[$number] }}" @endisset>
                    <input type="checkbox" name="numbers[]" value="{{ $number }}"@checked(in_array($number, $picks, true))>
                    <span>{{ $number }}</span>
                </label>
            @endfor
        </div>

        <div class="mb-3">
            <span class="keno-counter">{{ __('game::games.keno_picked', ['count' => count($picks), 'picks' => $picks_limit]) }}</span>
            <button type="button" class="btn btn-sm btn-outline-secondary ms-2 keno-clear">{{ __('game::games.keno_clear') }}</button>
        </div>

        @if ($errors->has('numbers'))
            <div class="text-danger mb-3">{{ textError('numbers') }}</div>
        @endif

        <div class="section-form mb-3 shadow">
            <div class="mb-3{{ hasError('bet') }}">
                <label for="bet" class="form-label">{{ __('game::games.bj_bet') }}</label>
                <input class="form-control" name="bet" id="bet" value="{{ $bet }}" required>
                <div class="invalid-feedback">{{ textError('bet') }}</div>
            </div>

            <button class="btn btn-primary">{{ __('game::games.keno_play') }}</button>
        </div>
    </form>

    @if ($game)
        {{-- Итог ждёт последний шар, иначе он известен раньше, чем поле догорело --}}
        <div class="fw-bold mb-3 keno-late" style="--last: {{ $draw }}">
            {{ __('game::games.keno_matched', ['count' => count($game['matched'])]) }}<br>

            @if ($game['win'])
                <span class="text-success">
                    <i class="fas fa-trophy"></i> {{ __('game::games.win_amount', ['money' => plural($game['win'], setting('moneyname'))]) }}
                </span>
            @else
                <span class="text-danger">{{ __('game::games.lost') }}</span>
            @endif
        </div>

        <div class="keno-balance" style="--last: {{ $draw }}">
            <span class="keno-balance-old">{{ __('game::games.balance', ['money' => plural($game['before'], setting('moneyname'))]) }}</span>
            <span class="keno-late">{{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}</span>
        </div>
    @else
        {{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}
    @endif
</div>
