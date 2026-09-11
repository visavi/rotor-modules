@php
    $step = 360 / count($wheel);
    $center = 160;
    $radius = 150;
    $labelRadius = 128;
    $type = $spin['type'] ?? old('type', 'red');
    $bet = $spin['bet'] ?? old('bet');
    $guess = $spin['guess'] ?? old('number', 0);

    // Сектор рисуется двумя точками на окружности и дугой между ними,
    // углы отсчитываются от указателя сверху по часовой стрелке
    $point = static function (float $angle, float $distance) use ($center) {
        $radians = deg2rad($angle);

        return [
            round($center + $distance * sin($radians), 2),
            round($center - $distance * cos($radians), 2),
        ];
    };

    $spinAngle = 0;

    if ($spin) {
        $index = array_search($spin['number'], array_column($wheel, 'number'), true);

        // Пять полных оборотов, чтобы колесо успело разогнаться
        $spinAngle = 360 * 5 - $index * $step;
    }
@endphp

<div id="roulette-box">
    <div class="roulette-wheel-holder mb-3">
        <div class="roulette-pointer"></div>

        {{-- Угол приходит с сервера, крутит колесо CSS — скрипт игре не нужен --}}
        <svg class="roulette-wheel @if ($spin) roulette-spinning @endif" style="--angle: {{ $spinAngle }}deg" viewBox="0 0 320 320" role="img" aria-label="{{ __('game::games.roulette') }}">
            <circle cx="{{ $center }}" cy="{{ $center }}" r="{{ $radius + 5 }}" class="roulette-rim"></circle>

            @foreach ($wheel as $index => $sector)
                @php
                    [$startX, $startY] = $point($index * $step - $step / 2, $radius);
                    [$endX, $endY] = $point($index * $step + $step / 2, $radius);
                    [$labelX, $labelY] = $point($index * $step, $labelRadius);
                @endphp

                <path class="roulette-sector roulette-{{ $sector['color'] }}"
                      d="M {{ $center }} {{ $center }} L {{ $startX }} {{ $startY }} A {{ $radius }} {{ $radius }} 0 0 1 {{ $endX }} {{ $endY }} Z"></path>

                <text class="roulette-label" x="{{ $labelX }}" y="{{ $labelY }}"
                      transform="rotate({{ round($index * $step, 2) }} {{ $labelX }} {{ $labelY }})">{{ $sector['number'] }}</text>
            @endforeach

            <circle cx="{{ $center }}" cy="{{ $center }}" r="30" class="roulette-hub"></circle>
        </svg>
    </div>

    @if ($spin)
        {{-- Число и выигрыш открываются, когда колесо остановилось --}}
        <div class="fw-bold mb-3 roulette-late">
            <span class="roulette-result roulette-{{ $spin['color'] }}">{{ $spin['number'] }}</span>
            {{ __('game::games.roulette_colors.' . $spin['color']) }}

            @if ($spin['win'])
                <div class="text-success">
                    <i class="fas fa-trophy"></i> {{ __('game::games.win_amount', ['money' => plural($spin['win'], setting('moneyname'))]) }}
                </div>
            @else
                <div class="text-danger">{{ __('game::games.lost') }}</div>
            @endif
        </div>
    @endif

    <div class="section-form mb-3 shadow">
        <form action="/games/roulette/spin" method="post" data-ajax data-ajax-replace="#roulette-box" data-ajax-swap="outer">
            @csrf

            <div class="mb-3{{ hasError('bet') }}">
                <label for="bet" class="form-label">{{ __('game::games.bj_bet') }}</label>
                <input class="form-control" name="bet" id="bet" value="{{ $bet }}" required>
                <div class="invalid-feedback">{{ textError('bet') }}</div>
            </div>

            <div class="mb-3{{ hasError('type') }}">
                <label for="type" class="form-label">{{ __('game::games.roulette_bet_type') }}</label>
                <select class="form-select" name="type" id="type">
                    @foreach ($bets as $name => $multiplier)
                        <option value="{{ $name }}"@selected($type === $name)>{{ __('game::games.roulette_bets.' . $name) }} (x{{ $multiplier }})</option>
                    @endforeach
                </select>
                <div class="invalid-feedback">{{ textError('type') }}</div>
            </div>

            <div class="mb-3{{ hasError('number') }} roulette-number-field" @if ($type !== 'number') hidden @endif>
                <label for="number" class="form-label">{{ __('game::games.roulette_number_label') }}</label>
                <input class="form-control" type="number" min="0" max="36" name="number" id="number" value="{{ $guess }}">
                <div class="invalid-feedback">{{ textError('number') }}</div>
            </div>

            <button class="btn btn-primary">{{ __('game::games.roulette_spin') }}</button>
        </form>
    </div>

    {{-- Пока колесо крутится, баланс показывается на момент до спина, иначе он выдает исход --}}
    @if ($spin)
        <div class="roulette-balance">
            <span class="roulette-balance-old">{{ __('game::games.balance', ['money' => plural($spin['before'], setting('moneyname'))]) }}</span>
            <span class="roulette-late">{{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}</span>
        </div>
    @else
        {{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}
    @endif
    <br>
</div>
