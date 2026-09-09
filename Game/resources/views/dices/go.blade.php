@extends('layout')

@section('title', __('game::games.your_turn'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item"><a href="/games/dices">{{ __('game::games.dices') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.your_turn') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    {{ __('game::games.dices_your') }}<br>
    <img src="/assets/modules/games/dices/{{ $num[0] }}.gif" alt="image"> {{ __('game::games.dices_and') }} <img src="/assets/modules/games/dices/{{ $num[1] }}.gif" alt="image"><br><br>

    {{ __('game::games.dices_banker') }}<br>
    <img src="/assets/modules/games/dices/{{ $num[2] }}.gif" alt="image"> {{ __('game::games.dices_and') }} <img src="/assets/modules/games/dices/{{ $num[3] }}.gif" alt="image"><br><br>

    <div class="fw-bold">
        <i class="fas fa-trophy"></i> {!! $result !!}
    </div>

    <a class="btn btn-primary" href="/games/dices/go?rand={{ mt_rand(1000, 99999) }}">{{ __('game::games.play') }}</a><br><br>

    {{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}<br>
@stop
