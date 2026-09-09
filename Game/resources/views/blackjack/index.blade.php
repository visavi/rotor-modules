@extends('layout')

@section('title', __('game::games.blackjack'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.blackjack') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <img src="/assets/modules/games/cards/44.png" alt="image">
    <img src="/assets/modules/games/cards/18.png" alt="image">
    <img src="/assets/modules/games/cards/27.png" alt="image">
    <img src="/assets/modules/games/cards/45.png" alt="image">
    <br><br>

    @if (session()->missing('blackjack.bet'))
        <div class="section-form mb-3 shadow">
            <form action="/games/blackjack/bet" method="post">
                @csrf
                <div class="mb-3{{ hasError('bet') }}">
                    <label for="bet" class="form-label">{{ __('game::games.bj_bet') }}</label>
                    <input class="form-control" name="bet" id="bet" value="{{ old('bet') }}" required>
                    <div class="invalid-feedback">{{ textError('bet') }}</div>
                </div>

                <button class="btn btn-primary">{{ __('game::games.play') }}</button>
            </form>
        </div>
    @else
        {{ __('game::games.bj_bets_made', ['money' => plural(session()->get('blackjack.bet') * 2, setting('moneyname'))]) }}<br><br>
        <b><a href="/games/blackjack/game?rand={{ random_int(1000, 9999) }}">{{ __('game::games.bj_return') }}</a></b><br><br>
    @endif

    {{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}<br><br>

    <i class="fa fa-question-circle"></i> <a href="/games/blackjack/rules">{{ __('game::games.rules') }}</a><br>
@stop
