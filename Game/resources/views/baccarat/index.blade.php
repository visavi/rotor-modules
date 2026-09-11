@extends('layout')

@section('title', __('game::games.baccarat'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.baccarat') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    {{ __('game::games.baccarat_intro') }}<br><br>

    @include('game::baccarat/_table')
    <br><br>

    <div class="section-form mb-3 shadow">
        <form action="/games/baccarat/deal" method="post">
            @csrf

            <div class="mb-3{{ hasError('bet') }}">
                <label for="bet" class="form-label">{{ __('game::games.bj_bet') }}</label>
                <input class="form-control" name="bet" id="bet" value="{{ old('bet') }}" required>
                <div class="invalid-feedback">{{ textError('bet') }}</div>
            </div>

            <div class="mb-3{{ hasError('type') }}">
                <label for="type" class="form-label">{{ __('game::games.baccarat_bet_type') }}</label>
                <select class="form-select" name="type" id="type">
                    @foreach ($bets as $name => $multiplier)
                        <option value="{{ $name }}"@selected(old('type', 'player') === $name)>{{ __('game::games.baccarat_bets.' . $name) }} (x{{ $multiplier }})</option>
                    @endforeach
                </select>
                <div class="invalid-feedback">{{ textError('type') }}</div>
            </div>

            <button class="btn btn-primary">{{ __('game::games.baccarat_deal') }}</button>
        </form>
    </div>

    {{ __('game::games.baccarat_rules') }}<br>
    {{ __('game::games.baccarat_rules_third') }}<br>
    {{ __('game::games.baccarat_rules_five') }}<br>
    {{ __('game::games.baccarat_rules_bets') }}
@stop

@push('styles')
    <style>
        /* Шаг задаётся разметкой, задержка считается от него — скрипт не нужен */
        .baccarat-card {
            margin-right: 0.25rem;
        }

        .baccarat-deal,
        .baccarat-late {
            animation: baccarat-deal 0.3s ease-out backwards;
            animation-delay: calc(var(--step, 0) * 0.6s);
        }

        /* Карта банкира — стопка из рубашки и лица, вскрытие меняет их местами */
        .baccarat-flip {
            display: inline-grid;
            margin-right: 0.25rem;
            vertical-align: middle;
        }

        .baccarat-flip > * {
            grid-area: 1 / 1;
            margin-right: 0;
        }

        .baccarat-back {
            animation: baccarat-hide 0.1s linear forwards;
            animation-delay: calc(var(--reveal, 0) * 0.6s);
        }

        .baccarat-back.baccarat-deal {
            animation:
                baccarat-deal 0.3s ease-out backwards,
                baccarat-hide 0.1s linear forwards;
            animation-delay: calc(var(--step, 0) * 0.6s), calc(var(--reveal, 0) * 0.6s);
        }

        .baccarat-face {
            animation: baccarat-deal 0.3s ease-out backwards;
            animation-delay: calc(var(--reveal, 0) * 0.6s);
        }

        /* Партия ждёт решения игрока — вскрывать нечего, рубашка остаётся лежать */
        .baccarat-closed .baccarat-back,
        .baccarat-closed .baccarat-back.baccarat-deal {
            animation: baccarat-deal 0.3s ease-out backwards;
            animation-delay: calc(var(--step, 0) * 0.6s);
        }

        .baccarat-balance {
            display: grid;
        }

        .baccarat-balance > * {
            grid-area: 1 / 1;
        }

        .baccarat-balance-old {
            animation: baccarat-gone 0.1s linear forwards;
            animation-delay: calc(var(--step, 0) * 0.6s);
        }

        @keyframes baccarat-deal {
            from { opacity: 0; transform: translateY(-1rem); }
            to { opacity: 1; transform: none; }
        }

        @keyframes baccarat-hide {
            to { opacity: 0; visibility: hidden; }
        }

        @keyframes baccarat-gone {
            to { opacity: 0; visibility: hidden; }
        }

        @media (prefers-reduced-motion: reduce) {
            .baccarat-deal,
            .baccarat-late,
            .baccarat-back,
            .baccarat-face,
            .baccarat-balance-old {
                animation-duration: 0.01s;
                animation-delay: 0s;
            }
        }
    </style>
@endpush
