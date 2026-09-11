@extends('layout')

@section('title', __('game::games.miner'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item"><a href="/games/miner">{{ __('game::games.miner') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.your_turn') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @include('game::miner/_field')
@stop

@push('styles')
    <style>
        .miner-field {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 4px;
            max-width: 20rem;
        }

        /* Открытая клетка переворачивается, мина ещё и встряхивает поле */
        .miner-flip {
            animation: miner-flip 0.3s ease-out backwards;
        }

        .miner-boom {
            animation: miner-boom 0.4s ease-out backwards;
        }

        /* Остальные мины проявляются волной после взрыва */
        .miner-reveal {
            animation: miner-show 0.25s ease-out backwards;
            animation-delay: calc(var(--step) * 0.12s);
        }

        .miner-late {
            animation: miner-show 0.3s ease-out backwards;
            animation-delay: calc(var(--last, 0) * 0.12s + 0.3s);
        }

        .miner-balance {
            display: grid;
        }

        .miner-balance > * {
            grid-area: 1 / 1;
        }

        .miner-balance-old {
            animation: miner-gone 0.1s linear forwards;
            animation-delay: calc(var(--last, 0) * 0.12s + 0.3s);
        }

        @keyframes miner-flip {
            from { opacity: 0; transform: rotateY(90deg); }
            to { opacity: 1; transform: none; }
        }

        @keyframes miner-boom {
            0% { transform: scale(0.6); }
            40% { transform: scale(1.25); }
            60% { transform: scale(1) translateX(-4px); }
            80% { transform: translateX(4px); }
            100% { transform: none; }
        }

        @keyframes miner-show {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes miner-gone {
            to { opacity: 0; visibility: hidden; }
        }

        @media (prefers-reduced-motion: reduce) {
            .miner-flip,
            .miner-boom,
            .miner-reveal,
            .miner-late,
            .miner-balance-old {
                animation-duration: 0.01s;
                animation-delay: 0s;
            }
        }
    </style>
@endpush
