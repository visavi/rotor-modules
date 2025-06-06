@extends('layout')

@section('title', 'Выбор наперстка')

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item"><a href="/games/thimbles">{{ __('game::games.thimbles') }}</a></li>
            <li class="breadcrumb-item active">Выбор наперстка</li>
        </ol>
    </nav>
@stop

@section('content')
    <a href="/games/thimbles/go?thimble=1&amp;rand={{ mt_rand(1000, 99999) }}"><img src="/assets/modules/games/thimbles/2.gif" alt="image"></a>
    <a href="/games/thimbles/go?thimble=2&amp;rand={{ mt_rand(1000, 99999) }}"><img src="/assets/modules/games/thimbles/2.gif" alt="image"></a>
    <a href="/games/thimbles/go?thimble=3&amp;rand={{ mt_rand(1000, 99999) }}"><img src="/assets/modules/games/thimbles/2.gif" alt="image"></a><br><br>

    Выберите наперсток в котором может находится шарик<br>

    У вас в наличии: {{ plural($user->money, setting('moneyname')) }}<br><br>
@stop
