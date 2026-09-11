@extends('layout')

@section('title', __('game::games.module'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active">{{ __('game::games.module') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @php
        // Цвет и иконка живут рядом с адресом: новая игра — одна строка, а не блок разметки
        $games = [
            ['url' => '/games/blackjack', 'icon' => 'fas fa-coins',            'color' => '#c9a227', 'title' => __('game::games.blackjack')],
            ['url' => '/games/dices',     'icon' => 'fas fa-dice',             'color' => '#c62828', 'title' => __('game::games.dices')],
            ['url' => '/games/thimbles',  'icon' => 'fas fa-beer',             'color' => '#a1672f', 'title' => __('game::games.thimbles')],
            ['url' => '/games/guess',     'icon' => 'fas fa-sort-numeric-up-alt', 'color' => '#1976d2', 'title' => __('game::games.guess')],
            ['url' => '/games/bandit',    'icon' => 'fas fa-dollar-sign',      'color' => '#7b1fa2', 'title' => __('game::games.slot')],
            ['url' => '/games/safe',      'icon' => 'fas fa-piggy-bank',       'color' => '#388e3c', 'title' => __('game::games.safe')],
            ['url' => '/games/miner',     'icon' => 'fas fa-bomb',             'color' => '#e64a19', 'title' => __('game::games.miner')],
            ['url' => '/games/roulette',  'icon' => 'fas fa-record-vinyl',     'color' => '#ad1457', 'title' => __('game::games.roulette')],
            ['url' => '/games/baccarat',  'icon' => 'fas fa-clone',            'color' => '#00838f', 'title' => __('game::games.baccarat')],
            ['url' => '/games/keno',      'icon' => 'fas fa-th',               'color' => '#5e35b1', 'title' => __('game::games.keno')],
            ['url' => '/games/highlow',   'icon' => 'fas fa-arrows-alt-v',     'color' => '#0288d1', 'title' => __('game::games.highlow')],
        ];
    @endphp

    <div class="container">
        <div class="row g-3 games-grid">
            @hook('gamesStart')

            @foreach ($games as $game)
                <div class="col-md-4 col-sm-6">
                    <div class="section game-card shadow" style="--game-color: {{ $game['color'] }}">
                        <i class="{{ $game['icon'] }} fa-5x"></i>
                        <a href="{{ $game['url'] }}" class="h5 stretched-link">{{ $game['title'] }}</a>
                    </div>
                </div>
            @endforeach

            @hook('gamesEnd')
        </div>
    </div>
@stop

@push('styles')
    <style>
        /* Карточки тянутся до высоты самой высокой в ряду: подпись в две строки
           или приписка о джек-поте больше не ломают сетку */
        .games-grid > div {
            display: flex;
        }

        .games-grid .game-card {
            position: relative;
            display: flex;
            flex: 1;
            flex-direction: column;
            gap: 0.75rem;
            align-items: center;
            justify-content: flex-start;
            text-align: center;
            border-top: 3px solid var(--game-color, transparent);
            /* Ссылка растянута на всю карточку (stretched-link), поэтому и курсор её */
            cursor: pointer;
        }

        .games-grid .game-card i {
            color: var(--game-color, inherit);
        }

        /* Подпись прижата к низу, чтобы иконки стояли на одной линии */
        .games-grid .game-card > :last-child {
            margin-top: auto;
        }

        .games-grid .game-card a {
            text-decoration: none;
        }

        .games-grid .game-card:hover {
            box-shadow: 0 0 0 1px var(--game-color, transparent), var(--bs-box-shadow) !important;
        }
    </style>
@endpush
