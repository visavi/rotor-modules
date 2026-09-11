@extends('layout')

@section('title', __('game::games.your_turn'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item"><a href="/games/blackjack">{{ __('game::games.blackjack') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.your_turn') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @include('game::blackjack/_table')
@stop

@push('styles')
    <style>
        /* Шаг задаётся разметкой, задержка считается от него — скрипт не нужен */
        .bj-deal,
        .bj-late {
            animation: bj-deal 0.3s ease-out backwards;
            animation-delay: calc(var(--step, 0) * 0.6s);
        }

        .bj-balance {
            display: grid;
        }

        .bj-balance > * {
            grid-area: 1 / 1;
        }

        .bj-balance-old {
            animation: bj-gone 0.1s linear forwards;
            animation-delay: calc(var(--step, 0) * 0.6s);
        }

        @keyframes bj-deal {
            from { opacity: 0; transform: translateY(-1rem); }
            to { opacity: 1; transform: none; }
        }

        @keyframes bj-gone {
            to { opacity: 0; visibility: hidden; }
        }

        @media (prefers-reduced-motion: reduce) {
            .bj-deal,
            .bj-late,
            .bj-balance-old {
                animation-duration: 0.01s;
                animation-delay: 0s;
            }
        }
    </style>
@endpush
