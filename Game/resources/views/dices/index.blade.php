@extends('layout')

@section('title', __('game::games.dices'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.dices') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @include('game::dices/_table')

    {{ __('game::games.press_play') }}<br>
    {{ __('game::games.dices_bet', ['money' => plural(Modules\Game\Http\Controllers\DiceController::BET, setting('moneyname'))]) }}<br>
    {{ __('game::games.win_reward', ['money' => plural(Modules\Game\Http\Controllers\DiceController::WIN, setting('moneyname'))]) }}<br>
    {{ __('game::games.dices_draw') }}<br>
    {{ __('game::games.lets_go') }}<br>
@stop

@push('styles')
    <style>
        .dices-row {
            --dice: 56px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .dices-cup {
            /* Окно ровно в одну грань, остальная лента за краем */
            width: var(--dice);
            height: var(--dice);
            overflow: hidden;
            border-radius: 0.4rem;
        }

        .dices-strip {
            display: flex;
            flex-direction: column;
            /* Без анимации лента сразу стоит на выпавшей грани */
            transform: translateY(calc(var(--stop) * var(--dice) * -1));
        }

        .dices-face {
            display: block;
            width: var(--dice);
            height: var(--dice);
        }

        /* Кубики останавливаются по очереди: сначала свои, потом банкира */
        .dices-rolling .dices-strip {
            animation: dices-roll 0.8s cubic-bezier(0.2, 0.85, 0.3, 1) backwards;
            animation-delay: calc(var(--step) * 0.18s);
        }

        .dices-rolling .dices-cup {
            animation: dices-shake 0.8s ease-out backwards;
            animation-delay: calc(var(--step) * 0.18s);
        }

        .dices-sum {
            padding: 0.15rem 0.6rem;
            font-weight: 700;
            background: rgba(128, 128, 128, .2);
            border-radius: 0.4rem;
        }

        .dices-late {
            animation: dices-appear 0.3s ease-out backwards;
            animation-delay: 1.35s;
        }

        .dices-balance {
            display: grid;
        }

        .dices-balance > * {
            grid-area: 1 / 1;
        }

        .dices-balance-old {
            animation: dices-gone 0.1s linear forwards;
            animation-delay: 1.35s;
        }

        @keyframes dices-roll {
            from { transform: translateY(0); }
            to { transform: translateY(calc(var(--stop) * var(--dice) * -1)); }
        }

        @keyframes dices-shake {
            0% { transform: rotate(-8deg); }
            50% { transform: rotate(7deg); }
            100% { transform: rotate(0); }
        }

        @keyframes dices-appear {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes dices-gone {
            to { opacity: 0; visibility: hidden; }
        }

        @media (prefers-reduced-motion: reduce) {
            .dices-rolling .dices-strip,
            .dices-rolling .dices-cup,
            .dices-late,
            .dices-balance-old {
                animation-duration: 0.01s;
                animation-delay: 0s;
            }
        }
    </style>
@endpush
