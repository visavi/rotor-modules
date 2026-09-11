@extends('layout')

@section('title', __('game::games.roulette'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.roulette') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @php
        $step = 360 / count($wheel);
        $center = 160;
        $radius = 150;
        $labelRadius = 128;

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

    {{ __('game::games.roulette_intro') }}<br><br>

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
        <form action="/games/roulette/spin" method="post">
            @csrf

            <div class="mb-3{{ hasError('bet') }}">
                <label for="bet" class="form-label">{{ __('game::games.bj_bet') }}</label>
                <input class="form-control" name="bet" id="bet" value="{{ old('bet') }}" required>
                <div class="invalid-feedback">{{ textError('bet') }}</div>
            </div>

            <div class="mb-3{{ hasError('type') }}">
                <label for="type" class="form-label">{{ __('game::games.roulette_bet_type') }}</label>
                <select class="form-select" name="type" id="type">
                    @foreach ($bets as $name => $multiplier)
                        <option value="{{ $name }}"@selected(old('type', 'red') === $name)>{{ __('game::games.roulette_bets.' . $name) }} (x{{ $multiplier }})</option>
                    @endforeach
                </select>
                <div class="invalid-feedback">{{ textError('type') }}</div>
            </div>

            <div class="mb-3{{ hasError('number') }}" id="roulette-number-field">
                <label for="number" class="form-label">{{ __('game::games.roulette_number_label') }}</label>
                <input class="form-control" type="number" min="0" max="36" name="number" id="number" value="{{ old('number', 0) }}">
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

    {{ __('game::games.roulette_payouts') }}
@stop

@push('styles')
    <style>
        .roulette-wheel-holder {
            position: relative;
            max-width: 320px;
        }

        .roulette-wheel {
            width: 100%;
            height: auto;
        }

        /* Один оборот колеса задаёт темп всей странице: результат ждёт его конца */
        .roulette-spinning {
            animation: roulette-spin 4s cubic-bezier(0.15, 0.85, 0.25, 1) forwards;
        }

        .roulette-late {
            animation: roulette-appear 0.3s ease-out backwards;
            animation-delay: 4s;
        }

        .roulette-balance {
            display: grid;
        }

        .roulette-balance > * {
            grid-area: 1 / 1;
        }

        .roulette-balance-old {
            animation: roulette-gone 0.1s linear forwards;
            animation-delay: 4s;
        }

        @keyframes roulette-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(var(--angle, 0deg)); }
        }

        @keyframes roulette-appear {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes roulette-gone {
            to { opacity: 0; visibility: hidden; }
        }

        .roulette-pointer {
            position: absolute;
            top: -4px;
            left: 50%;
            z-index: 1;
            width: 0;
            height: 0;
            transform: translateX(-50%);
            border-top: 16px solid #ffc107;
            border-right: 9px solid transparent;
            border-left: 9px solid transparent;
        }

        .roulette-sector.roulette-red { fill: #c62828; }
        .roulette-sector.roulette-black { fill: #212529; }
        .roulette-sector.roulette-zero { fill: #2e7d32; }
        .roulette-sector { stroke: #f8f9fa; stroke-width: 0.5; }
        .roulette-rim { fill: #6d4c41; }
        .roulette-hub { fill: #8d6e63; }

        .roulette-label {
            font-size: 12px;
            font-weight: 700;
            fill: #fff;
            text-anchor: middle;
            dominant-baseline: middle;
        }

        .roulette-result {
            display: inline-block;
            min-width: 2.5rem;
            padding: 0.25rem 0.5rem;
            color: #fff;
            font-size: 1.5rem;
            text-align: center;
            border-radius: 0.25rem;
        }

        .roulette-result.roulette-red { background: #c62828; }
        .roulette-result.roulette-black { background: #212529; }
        .roulette-result.roulette-zero { background: #2e7d32; }

        @media (prefers-reduced-motion: reduce) {
            .roulette-spinning,
            .roulette-late,
            .roulette-balance-old {
                animation-duration: 0.01s;
                animation-delay: 0s;
            }
        }
    </style>
@endpush

@push('scripts')
    <script type="module">
        // Поле числа нужно только ставке на конкретное число
        const type = document.getElementById('type');
        const field = document.getElementById('roulette-number-field');

        const toggle = () => field.hidden = type.value !== 'number';

        toggle();
        type.addEventListener('change', toggle);
    </script>
@endpush
