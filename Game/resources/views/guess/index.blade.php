@extends('layout')

@section('title', __('game::games.guess'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.guess') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @php use Modules\Game\Http\Controllers\GuessNumberController as Guess; @endphp

    <b>{{ __('game::games.guess_enter_range', ['min' => Guess::MIN, 'max' => Guess::MAX]) }}</b><br><br>

    @include('game::guess/_game')

    <hr>

    {{ __('game::games.guess_howto') }}<br>
    {{ __('game::games.guess_attempt_price', ['money' => plural($price, setting('moneyname'))]) }}<br>
    {{ __('game::games.guess_hint_info') }}<br>
    {{ __('game::games.guess_restart_info', ['count' => Guess::TRIES]) }}<br>
    <b>{{ __('game::games.guess_prizes', ['money' => plural($reward, setting('moneyname'))]) }}</b><br>
    <br>
    {{ __('game::games.lets_go') }}<br>
@stop

@push('styles')
    <style>
        .guess-scale {
            position: relative;
            height: 26px;
            border-radius: 0.3rem;
            background: rgba(108, 117, 125, .25);
            overflow: hidden;
        }

        /* Живой диапазон: то, что ещё не отсечено подсказками */
        .guess-band {
            position: absolute;
            top: 0;
            bottom: 0;
            left: var(--left);
            width: var(--width);
            background: #198754;
            opacity: .35;
            animation: guess-narrow 0.5s ease-out;
        }

        .guess-tick {
            position: absolute;
            top: 3px;
            bottom: 3px;
            width: 3px;
            margin-left: -1px;
            border-radius: 2px;
            background: #6c757d;
        }

        .guess-tick-exact {
            background: #198754;
        }

        .guess-fresh {
            animation: guess-drop 0.3s ease-out backwards;
            animation-delay: 0.4s;
        }

        .guess-range {
            display: flex;
            justify-content: space-between;
            font-size: .85rem;
            opacity: .75;
        }

        .guess-narrowed {
            font-weight: 700;
            opacity: 1;
        }

        .guess-history {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .guess-line {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .guess-number {
            display: inline-flex;
            min-width: 44px;
            height: 32px;
            padding: 0 6px;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #fff;
            background: #6c757d;
            border-radius: 0.3rem;
        }

        .guess-line-exact .guess-number {
            background: #198754;
        }

        .guess-input {
            max-width: 160px;
            font-size: 1.25rem;
            font-weight: 700;
            text-align: center;
        }

        /* Свежая попытка появляется после того, как шкала сузилась */
        .guess-reveal {
            animation: guess-flip 0.3s ease-out backwards;
            animation-delay: 0.4s;
        }

        .guess-late {
            animation: guess-appear 0.3s ease-out backwards;
            animation-delay: 0.7s;
        }

        .guess-balance {
            display: grid;
        }

        .guess-balance > * {
            grid-area: 1 / 1;
        }

        .guess-balance-old {
            animation: guess-gone 0.1s linear forwards;
            animation-delay: 0.7s;
        }

        @keyframes guess-narrow {
            from { left: var(--from-left); width: var(--from-width); }
        }

        @keyframes guess-drop {
            from { opacity: 0; transform: translateY(-14px); }
            to { opacity: 1; transform: none; }
        }

        @keyframes guess-flip {
            from { opacity: 0; transform: rotateX(90deg); }
            to { opacity: 1; transform: none; }
        }

        @keyframes guess-appear {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes guess-gone {
            to { opacity: 0; visibility: hidden; }
        }

        @media (prefers-reduced-motion: reduce) {
            .guess-band,
            .guess-fresh,
            .guess-reveal,
            .guess-late,
            .guess-balance-old {
                animation-duration: 0.01s;
                animation-delay: 0s;
            }
        }
    </style>
@endpush
