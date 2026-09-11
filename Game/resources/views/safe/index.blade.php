@extends('layout')

@section('title', __('game::games.safe'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.safe') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    {{ __('game::games.safe_dont_rush', ['name' => $user->getName()]) }}<br><br>

    @include('game::safe/_safe')

    <hr>

    {{ __('game::games.safe_prize', ['money' => plural($prize, setting('moneyname'))]) }}<br>
    {{ __('game::games.safe_price_info') }}<br>
    {{ __('game::games.safe_attempts_info', ['tries' => Modules\Game\Http\Controllers\SafeController::TRIES, 'length' => Modules\Game\Http\Controllers\SafeController::LENGTH]) }}<br><br>

    <b>{{ __('game::games.safe_help') }}</b><br>
    <span class="safe-mark safe-mark-exact">7</span> {{ __('game::games.safe_help_exact') }}<br>
    <span class="safe-mark safe-mark-moved">*</span> {{ __('game::games.safe_help_moved') }}<br>
    <span class="safe-mark safe-mark-absent">3</span> {{ __('game::games.safe_help_absent') }}<br>
@stop

@push('styles')
    <style>
        .safe-image {
            width: 120px;
            height: 120px;
        }

        .safe-code {
            display: flex;
            gap: 6px;
        }

        .safe-digit {
            width: 48px;
            font-size: 1.25rem;
            font-weight: 700;
            text-align: center;
        }

        .safe-history {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .safe-line {
            display: flex;
            gap: 4px;
        }

        .safe-mark {
            display: inline-flex;
            width: 32px;
            height: 32px;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            border-radius: 0.3rem;
        }

        /* Цифра на месте — зелёная, цифра не на месте — жёлтая, чужая — серая */
        .safe-mark-exact { color: #fff; background: #198754; }
        .safe-mark-moved { color: #000; background: #ffc107; }
        .safe-mark-absent { color: #fff; background: #6c757d; opacity: .6; }

        /* Метки свежей попытки открываются по очереди, задержка в CSS */
        .safe-reveal {
            animation: safe-flip 0.3s ease-out backwards;
            animation-delay: calc(var(--step) * 0.12s);
        }

        .safe-shake {
            animation: safe-shake 0.4s ease-in-out;
        }

        .safe-opened {
            animation: safe-open 0.5s ease-out backwards;
            animation-delay: 0.6s;
        }

        .safe-late {
            animation: safe-appear 0.3s ease-out backwards;
            animation-delay: 0.6s;
        }

        .safe-balance {
            display: grid;
        }

        .safe-balance > * {
            grid-area: 1 / 1;
        }

        .safe-balance-old {
            animation: safe-gone 0.1s linear forwards;
            animation-delay: 0.6s;
        }

        @keyframes safe-flip {
            from { opacity: 0; transform: rotateX(90deg); }
            to { opacity: 1; transform: none; }
        }

        @keyframes safe-shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-6px); }
            75% { transform: translateX(6px); }
        }

        @keyframes safe-open {
            from { opacity: 0; transform: scale(0.85) rotate(-4deg); }
            to { opacity: 1; transform: none; }
        }

        @keyframes safe-appear {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes safe-gone {
            to { opacity: 0; visibility: hidden; }
        }

        @media (prefers-reduced-motion: reduce) {
            .safe-reveal,
            .safe-shake,
            .safe-opened,
            .safe-late,
            .safe-balance-old {
                animation-duration: 0.01s;
                animation-delay: 0s;
            }
        }
    </style>
@endpush
