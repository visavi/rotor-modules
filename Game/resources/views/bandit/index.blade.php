@extends('layout')

@section('title',  __('game::games.slot'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.slot') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    {{ __('game::games.bandit_intro') }}<br><br>

    @include('game::bandit/_machine')

    <i class="fa fa-question-circle"></i> <a href="/games/bandit/faq">{{ __('game::games.rules') }}</a><br>
@stop

@push('styles')
    <style>
        .bandit-reels {
            display: flex;
            gap: 6px;
            width: max-content;
            max-width: 100%;
            padding: 10px;
            background: rgba(128, 128, 128, .15);
            border-radius: 0.5rem;
        }

        .bandit-reel {
            /* Окно барабана ровно в три ячейки, остальная лента за его краем */
            height: calc(var(--cell) * 3);
            overflow: hidden;
            background: var(--bs-body-bg);
            border-radius: 0.35rem;
        }

        .bandit-reels {
            --cell: 64px;
        }

        @media (max-width: 400px) {
            .bandit-reels { --cell: 52px; }
        }

        .bandit-strip {
            display: flex;
            flex-direction: column;
            /* Без анимации лента сразу стоит на выпавших символах */
            transform: translateY(calc(var(--stop) * var(--cell) * -1));
        }

        .bandit-cell {
            display: flex;
            width: var(--cell);
            height: var(--cell);
            align-items: center;
            justify-content: center;
        }

        .bandit-cell img {
            width: 70%;
            height: 70%;
        }

        /* Барабаны останавливаются слева направо, поэтому у каждого своя длительность */
        .bandit-spinning .bandit-strip {
            animation: bandit-spin calc(0.9s + var(--reel) * 0.45s) cubic-bezier(0.2, 0.8, 0.3, 1) backwards;
        }

        .bandit-hit {
            background: rgba(25, 135, 84, .2);
            animation: bandit-hit 0.4s ease-out backwards;
            animation-delay: 2.25s;
        }

        .bandit-late {
            animation: bandit-appear 0.3s ease-out backwards;
            animation-delay: 2.25s;
        }

        .bandit-balance {
            display: grid;
        }

        .bandit-balance > * {
            grid-area: 1 / 1;
        }

        .bandit-balance-old {
            animation: bandit-gone 0.1s linear forwards;
            animation-delay: 2.25s;
        }

        @keyframes bandit-spin {
            from { transform: translateY(0); }
            to { transform: translateY(calc(var(--stop) * var(--cell) * -1)); }
        }

        @keyframes bandit-hit {
            from { background: transparent; }
        }

        @keyframes bandit-appear {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes bandit-gone {
            to { opacity: 0; visibility: hidden; }
        }

        @media (prefers-reduced-motion: reduce) {
            .bandit-spinning .bandit-strip,
            .bandit-hit,
            .bandit-late,
            .bandit-balance-old {
                animation-duration: 0.01s;
                animation-delay: 0s;
            }
        }
    </style>
@endpush
