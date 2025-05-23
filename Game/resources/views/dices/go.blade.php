@extends('layout')

@section('title', 'Ваш ход')

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item"><a href="/games/dices">{{ __('game::games.dices') }}</a></li>
            <li class="breadcrumb-item active">Ваш ход</li>
        </ol>
    </nav>
@stop

@section('content')
    Ваши кости:<br>
    <img src="/assets/modules/games/dices/{{ $num[0] }}.gif" alt="image"> и <img src="/assets/modules/games/dices/{{ $num[1] }}.gif" alt="image"><br><br>

    У банкира:<br>
    <img src="/assets/modules/games/dices/{{ $num[2] }}.gif" alt="image"> и <img src="/assets/modules/games/dices/{{ $num[3] }}.gif" alt="image"><br><br>

    <div class="fw-bold">
        <i class="fas fa-trophy"></i> {!! $result !!}
    </div>

    <a class="btn btn-primary" href="/games/dices/go?rand={{ mt_rand(1000, 99999) }}">Играть</a><br><br>

    У вас в наличии: {{ plural($user->money, setting('moneyname')) }}<br>
@stop
