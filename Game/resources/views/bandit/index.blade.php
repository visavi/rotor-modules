@extends('layout')

@section('title',  __('game::games.slot'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.slot') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    Любишь азарт? А выигрывая, чувствуешь адреналин? Играй и получай призы<br><br>

    <img src="/assets/modules/games/bandit/1.gif" alt="image"> <img src="/assets/modules/games/bandit/2.gif" alt="image"> <img src="/assets/modules/games/bandit/3.gif" alt="image"><br>
    <img src="/assets/modules/games/bandit/8.gif" alt="image"> <img src="/assets/modules/games/bandit/8.gif" alt="image"> <img src="/assets/modules/games/bandit/8.gif" alt="image"><br>
    <img src="/assets/modules/games/bandit/5.gif" alt="image"> <img src="/assets/modules/games/bandit/6.gif" alt="image"> <img src="/assets/modules/games/bandit/7.gif" alt="image"><br><br>

    <a class="btn btn-primary" href="/games/bandit/go?rand={{ mt_rand(1000, 99999) }}">Играть</a><br><br>

    У вас в наличии: {{ plural($user->money, setting('moneyname')) }}<br><br>

    <i class="fa fa-question-circle"></i> <a href="/games/bandit/faq">Правила игры</a><br>
@stop
