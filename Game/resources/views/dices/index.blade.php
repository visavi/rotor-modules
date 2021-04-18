@extends('layout')

@section('title', __('Game::games.dices'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('Game::games.module') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Game::games.dices') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <img src="/assets/modules/games/dices/6.gif" alt="image"> и <img src="/assets/modules/games/dices/6.gif" alt="image"><br><br>

    <a class="btn btn-primary" href="/games/dices/go?rand={{ mt_rand(1000, 99999) }}">Играть</a><br><br>

    У вас в наличии: {{ plural($user->money, setting('moneyname')) }}<br><br>

    Для участия в игре нажмите "Играть"<br>
    За каждый выигрыш вы получите {{ plural(10, setting('moneyname')) }}<br>
    За каждый проигрыш у вас будут списывать по {{ plural(5, setting('moneyname')) }}<br>
    Итак дерзайте!<br>
@stop
