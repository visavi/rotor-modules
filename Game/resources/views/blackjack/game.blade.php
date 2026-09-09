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
    {{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}<br><br>

    {{-- Как за столом: банкир напротив, свои карты ближе к себе --}}
    <b>{{ __('game::games.bj_banker_cards') }}</b><br>

    @foreach ($blackjack['bankercards'] as $card)
        <img src="/assets/modules/games/cards/{{ $result ? $card : 0 }}.png" alt="image">
    @endforeach

    @if ($result)
        <br>{{ plural($scores['banker'], __('game::games.bj_points')) }}
    @endif

    <br><br>

    <b>{{ __('game::games.bj_your_cards') }}</b><br>

    @foreach ($blackjack['cards'] as $card)
        <img src="/assets/modules/games/cards/{{ $card }}.png" alt="image">
    @endforeach

    <br>{{ plural($scores['user'], __('game::games.bj_points')) }}<br>

    @if ($result)
        <div class="my-3 fw-bold">
            @if ($text)
                {{ $text }}<br>
            @endif

            @if ($result === 'victory')
                <span class="text-success">{{ __('game::games.victory') }}</span><br>
                {{ __('game::games.bj_won', ['money' => plural($amount, setting('moneyname'))]) }}
            @elseif ($result === 'lost')
                <span class="text-danger">{{ __('game::games.lost') }}</span><br>
                {{ __('game::games.bj_lost', ['money' => plural($amount, setting('moneyname'))]) }}
            @else
                {{ __('game::games.draw') }}<br>
                {{ __('game::games.bj_returned', ['money' => plural($amount, setting('moneyname'))]) }}
            @endif
        </div>

        <form action="/games/blackjack/bet" method="post" class="d-inline">
            @csrf
            <input type="hidden" name="bet" value="{{ $blackjack['bet'] }}">
            <button type="submit" class="btn btn-primary">{{ __('game::games.bj_repeat') }}</button>
        </form>
        <br><br>

        <i class="fa fa-coins"></i> <a href="/games/blackjack">{{ __('game::games.bj_new_bet') }}</a><br>
    @else
        <div class="my-3">{{ __('game::games.bj_stake', ['money' => plural($blackjack['bet'] * 2, setting('moneyname'))]) }}</div>

        <b><a class="btn btn-success" href="/games/blackjack/game?case=take&amp;rand={{ mt_rand(1000, 99999) }}">{{ __('game::games.bj_take_card') }}</a></b> {{ __('game::games.bj_or') }}
        <b><a class="btn btn-danger" href="/games/blackjack/game?case=end&amp;rand={{ mt_rand(1000, 99999) }}">{{ __('game::games.bj_open') }}</a></b>
        <br><br>
    @endif
@stop
