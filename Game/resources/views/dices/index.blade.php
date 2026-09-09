@extends('layout')

@section('title', __('game::games.dices'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.dices') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <img src="/assets/modules/games/dices/6.gif" alt="image"> {{ __('game::games.dices_and') }} <img src="/assets/modules/games/dices/6.gif" alt="image"><br><br>

    <a class="btn btn-primary" href="/games/dices/go?rand={{ mt_rand(1000, 99999) }}">{{ __('game::games.play') }}</a><br><br>

    {{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}<br><br>

    {{ __('game::games.press_play') }}<br>
    {{ __('game::games.win_reward', ['money' => plural(10, setting('moneyname'))]) }}<br>
    {{ __('game::games.lose_penalty', ['money' => plural(5, setting('moneyname'))]) }}<br>
    {{ __('game::games.lets_go') }}<br>
@stop
