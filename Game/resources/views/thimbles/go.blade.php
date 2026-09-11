@extends('layout')

@section('title', __('game::games.thimbles_game'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item"><a href="/games/thimbles">{{ __('game::games.thimbles') }}</a></li>
            <li class="breadcrumb-item"><a href="/games/thimbles/choice">{{ __('game::games.thimbles_choice') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.thimbles_game') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @php
        // Сначала поднимается выбранный напёрсток, следом — тот, где шарик
        $opens = [$thimble => $thimble === $randThimble ? 'thimble-ball' : 'thimble-up'];

        if ($thimble !== $randThimble) {
            $opens[$randThimble] = 'thimble-ball';
        }

        $steps = array_flip(array_keys($opens));

        // Итог появляется после последнего подъёма, ещё через паузу напёрстки закрываются
        $resultStep = count($opens);
    @endphp

    <div style="--result-step: {{ $resultStep }}">
        @include('game::thimbles/_row')

        <br><br>

        {{ __('game::games.thimbles_pick') }}<br><br>

        <div class="fw-bold thimble-late">
            <i class="fas fa-trophy"></i> {!! $result !!}
        </div>

        <div class="thimble-balance">
            <span class="thimble-balance-old">{{ __('game::games.balance', ['money' => plural($before, setting('moneyname'))]) }}</span>
            <span class="thimble-late">{{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}</span>
        </div>
        <br><br>
    </div>
@stop

@push('styles')
    <style>
        /* Шаг задаётся разметкой, задержки считаются от него — скрипт игре не нужен */
        .thimble-link {
            text-decoration: none;
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
            .thimble-lift,
            .thimble-open,
            .thimble-closed,
            .thimble-late,
            .thimble-balance-old {
                animation-duration: 0.01s;
            }
        }
    </style>
@endpush
