@extends('layout')

@section('title', 'Ваш ход')

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item"><a href="/games/blackjack">{{ __('game::games.blackjack') }}</a></li>
            <li class="breadcrumb-item active">Ваш ход</li>
        </ol>
    </nav>
@stop

@section('content')
    У вас в наличии: {{ plural($user->money, setting('moneyname')) }}<br><br>

    <b>Ваши карты:</b><br>

    @foreach ($blackjack['cards'] as $card)
        <img src="/assets/modules/games/cards/{{ $card }}.png" alt="image">
    @endforeach

    <br>{{ plural($scores['user'], ['очко', 'очка', 'очков']) }}<br><br>

    @if ($result)
        <b>Карты банкира:</b><br>

        @foreach ($blackjack['bankercards'] as $card)
            <img src="/assets/modules/games/cards/{{ $card }}.png" alt="image">
        @endforeach

        <br>{{ plural($scores['banker'], ['очко', 'очка', 'очков']) }}<br>

        <div class="my-3 fw-bold">
            @if ($text)
                {{ $text }}<br>
            @endif

            {!! $result !!}
        </div>

        <a class="btn btn-primary" href="/games/blackjack/bet?bet={{ $blackjack['bet'] }}&amp;_token={{ csrf_token() }}">Повторить</a><br><br>

        <i class="fa fa-coins"></i> <a href="/games/blackjack">Новая ставка</a><br>
    @else
        <b>Карты банкира:</b><br>
        @foreach ($blackjack['bankercards'] as $card)
            <img src="/assets/modules/games/cards/0.png" alt="image">
        @endforeach

        <div class="my-3">На кону: {{ plural($blackjack['bet'] * 2, setting('moneyname')) }}</div>

        <b><a class="btn btn-success" href="/games/blackjack/game?case=take&amp;rand={{ mt_rand(1000, 99999) }}">Взять карту</a></b> или
        <b><a class="btn btn-danger" href="/games/blackjack/game?case=end&amp;rand={{ mt_rand(1000, 99999) }}">Открыться</a></b>
        <br><br>
    @endif
@stop
