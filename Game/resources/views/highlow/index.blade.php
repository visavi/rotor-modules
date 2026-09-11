@extends('layout')

@section('title', __('game::games.highlow'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.highlow') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    {{ __('game::games.hl_intro') }}<br><br>

    @include('game::highlow/_table')

    <br>
    <i class="fa fa-question-circle"></i> <a href="/games/highlow/rules">{{ __('game::games.rules') }}</a>
@stop

@push('styles')
    <style>
        .hl-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }

        .hl-card {
            width: 64px;
            height: auto;
        }

        /* Задержка живёт в CSS, поэтому ход через ajax анимируется без скрипта */
        .hl-deal {
            animation: hl-deal 0.4s ease-out backwards;
        }

        .hl-late {
            animation: hl-appear 0.3s ease-out backwards;
            animation-delay: 0.4s;
        }

        .hl-balance {
            display: grid;
        }

        .hl-balance > * {
            grid-area: 1 / 1;
        }

        .hl-balance-old {
            animation: hl-gone 0.1s linear forwards;
            animation-delay: 0.4s;
        }

        @keyframes hl-deal {
            from {
                opacity: 0;
                transform: translateY(-25px) rotate(-8deg);
            }
            to {
                opacity: 1;
                transform: none;
            }
        }

        @keyframes hl-appear {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes hl-gone {
            to { opacity: 0; visibility: hidden; }
        }

        @media (prefers-reduced-motion: reduce) {
            .hl-deal,
            .hl-late,
            .hl-balance-old {
                animation-duration: 0.01s;
                animation-delay: 0s;
            }
        }
    </style>
@endpush
