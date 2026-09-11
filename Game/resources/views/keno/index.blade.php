@extends('layout')

@section('title', __('game::games.keno'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.keno') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @php
        ['field' => $field, 'draw' => $draw, 'min' => $min, 'max' => $max] = $limits;

        // Шары загораются по очереди: номер шара задаёт задержку, всё остальное делает CSS
        $steps = $game ? array_flip($game['drawn']) : [];
        $picks = $game['picks'] ?? array_map('intval', (array) old('numbers', []));
    @endphp

    {{ __('game::games.keno_intro', ['min' => $min, 'max' => $max, 'field' => $field, 'draw' => $draw]) }}<br><br>

    <form action="/games/keno/play" method="post">
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
            <span id="keno-counter">{{ __('game::games.keno_picked', ['count' => count($picks)]) }}</span>
            <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="keno-clear">{{ __('game::games.keno_clear') }}</button>
        </div>

        @if ($errors->has('numbers'))
            <div class="text-danger mb-3">{{ textError('numbers') }}</div>
        @endif

        <div class="section-form mb-3 shadow">
            <div class="mb-3{{ hasError('bet') }}">
                <label for="bet" class="form-label">{{ __('game::games.bj_bet') }}</label>
                <input class="form-control" name="bet" id="bet" value="{{ old('bet') }}" required>
                <div class="invalid-feedback">{{ textError('bet') }}</div>
            </div>

            <button class="btn btn-primary">{{ __('game::games.keno_play') }}</button>
        </div>
    </form>

    @if ($game)
        {{-- Итог ждёт последний шар, иначе он известен раньше, чем поле догорело --}}
        <div class="fw-bold mb-3 keno-late" style="--last: {{ $draw }}">
            {{ __('game::games.keno_matched', ['count' => count($game['matched'])]) }}<br>

            @if ($game['win'] > $game['bet'])
                <span class="text-success">
                    <i class="fas fa-trophy"></i> {{ __('game::games.win_amount', ['money' => plural($game['win'], setting('moneyname'))]) }}
                </span>
            @elseif ($game['win'])
                <span class="text-warning">{{ __('game::games.keno_refund', ['money' => plural($game['win'], setting('moneyname'))]) }}</span>
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

    <br>
    <i class="fa fa-question-circle"></i> <a href="/games/keno/rules">{{ __('game::games.rules') }}</a>
@stop

@push('styles')
    <style>
        .keno-field {
            display: grid;
            grid-template-columns: repeat(10, minmax(0, 1fr));
            gap: 4px;
            max-width: 520px;
        }

        /* На узком экране десять колонок не читаются, поэтому поле складывается вдвое */
        @media (max-width: 576px) {
            .keno-field { grid-template-columns: repeat(5, minmax(0, 1fr)); }
        }

        .keno-cell input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .keno-cell span {
            display: block;
            padding: 0.35rem 0;
            font-size: 0.85rem;
            text-align: center;
            border: 1px solid rgba(128, 128, 128, 0.4);
            border-radius: 0.25rem;
            cursor: pointer;
            user-select: none;
        }

        .keno-cell input:checked + span {
            color: #fff;
            font-weight: 700;
            background: #0d6efd;
            border-color: #0d6efd;
        }

        /* Вытянутое число подсвечивается зелёным, совпавшее с отметкой — поверх синего */
        .keno-drawn span {
            animation: keno-hit 0.25s ease-out forwards;
            animation-delay: calc(var(--step) * 0.18s);
        }

        .keno-late {
            animation: keno-appear 0.3s ease-out backwards;
            animation-delay: calc(var(--last) * 0.18s);
        }

        .keno-balance {
            display: grid;
        }

        .keno-balance > * {
            grid-area: 1 / 1;
        }

        .keno-balance-old {
            animation: keno-gone 0.1s linear forwards;
            animation-delay: calc(var(--last) * 0.18s);
        }

        @keyframes keno-hit {
            from { transform: scale(1); }
            50% { transform: scale(1.25); }
            to {
                transform: scale(1);
                color: #fff;
                background: #198754;
                border-color: #198754;
            }
        }

        @keyframes keno-appear {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes keno-gone {
            to { opacity: 0; visibility: hidden; }
        }

        @media (prefers-reduced-motion: reduce) {
            .keno-drawn span,
            .keno-late,
            .keno-balance-old {
                animation-duration: 0.01s;
                animation-delay: 0s;
            }
        }
    </style>
@endpush

@push('scripts')
    <script type="module">
        // Больше максимума не отметить: лишние клетки просто не отмечаются
        const max = {{ $max }};
        const field = document.querySelector('.keno-field');
        const counter = document.getElementById('keno-counter');
        const boxes = () => [...field.querySelectorAll('input')]
        const template = @json(__('game::games.keno_picked', ['count' => ':count']));

        const recount = () => {
            const checked = boxes().filter(box => box.checked)
            counter.textContent = template.replace(':count', String(checked.length))
        }

        field.addEventListener('change', (event) => {
            const checked = boxes().filter(box => box.checked)

            if (checked.length > max) {
                event.target.checked = false
            }

            recount()
        })

        document.getElementById('keno-clear').addEventListener('click', () => {
            boxes().forEach(box => box.checked = false)
            recount()
        })
    </script>
@endpush
