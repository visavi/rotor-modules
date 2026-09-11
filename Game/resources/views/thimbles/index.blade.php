@extends('layout')

@section('title', __('game::games.thimbles'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.thimbles') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @include('game::thimbles/_table')

    <hr>

    {{ __('game::games.thimbles_howto') }}<br>
    {{ __('game::games.win_reward', ['money' => plural($win - $bet, setting('moneyname'))]) }}<br>
    {{ __('game::games.lose_penalty', ['money' => plural($bet, setting('moneyname'))]) }}<br>
    {{ __('game::games.lets_go') }}<br>
@stop

@push('styles')
    <style>
        /* Шаг задаётся разметкой, задержки считаются от него — скрипт игре не нужен */
        .thimble-row {
            display: flex;
            gap: 4px;
        }

        .thimble-button {
            padding: 0;
            border: 0;
            background: none;
            line-height: 0;
            cursor: pointer;
        }

        .thimble-button:disabled {
            cursor: default;
        }

        .thimble-stack {
            display: inline-grid;
            vertical-align: middle;
        }

        .thimble-stack > * {
            grid-area: 1 / 1;
        }

        .thimble-closed {
            animation:
                thimble-gone 0.1s linear forwards,
                thimble-back 0.35s ease-out forwards;
            animation-delay: calc(var(--step, 0) * 0.7s), var(--hide);
        }

        .thimble-open {
            animation:
                thimble-lift 0.35s ease-out backwards,
                thimble-gone 0.1s linear forwards;
            animation-delay: calc(var(--step, 0) * 0.7s), var(--hide);
        }

        .thimble-late {
            animation: thimble-show 0.3s ease-out backwards;
            animation-delay: calc(var(--result-step, 0) * 0.7s);
        }

        .thimble-balance {
            display: grid;
        }

        .thimble-balance > * {
            grid-area: 1 / 1;
        }

        .thimble-balance-old {
            animation: thimble-gone 0.1s linear forwards;
            animation-delay: calc(var(--result-step, 0) * 0.7s);
        }

        /* Пауза, после которой стол снова готов к новому выбору */
        .thimble-stack {
            --hide: calc(var(--result-step, 1) * 0.7s + 1.6s);
        }

        @keyframes thimble-lift {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: none; }
        }

        @keyframes thimble-show {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes thimble-gone {
            to { opacity: 0; visibility: hidden; }
        }

        @keyframes thimble-back {
            from { opacity: 0; }
            to { opacity: 1; visibility: visible; }
        }

        @media (prefers-reduced-motion: reduce) {
            .thimble-closed,
            .thimble-open,
            .thimble-late,
            .thimble-balance-old {
                animation-duration: 0.01s;
                animation-delay: 0s;
            }
        }
    </style>
@endpush
