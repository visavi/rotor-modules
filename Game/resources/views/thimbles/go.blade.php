@extends('layout')

@section('title', 'Игра')

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item"><a href="/games/thimbles">{{ __('game::games.thimbles') }}</a></li>
            <li class="breadcrumb-item"><a href="/games/thimbles/choice">Выбор наперстка</a></li>
            <li class="breadcrumb-item active">Игра</li>
        </ol>
    </nav>
@stop

@section('content')
    <a href="/games/thimbles/go?thimble=1&amp;rand={{ mt_rand(1000, 99999) }}"><img src="/assets/modules/games/thimbles/{{ $randThimble === 1 ? 3 : 2 }}.gif" alt="image"></a>
    <a href="/games/thimbles/go?thimble=2&amp;rand={{ mt_rand(1000, 99999) }}"><img src="/assets/modules/games/thimbles/{{ $randThimble === 2 ? 3 : 2 }}.gif" alt="image"></a>
    <a href="/games/thimbles/go?thimble=3&amp;rand={{ mt_rand(1000, 99999) }}"><img src="/assets/modules/games/thimbles/{{ $randThimble === 3 ? 3 : 2 }}.gif" alt="image"></a><br><br>

    Выберите наперсток в котором может находится шарик<br><br>

    <div class="fw-bold">
        <i class="fas fa-trophy"></i> {!! $result !!}
    </div>

    У вас в наличии: {{ plural($user->money, setting('moneyname')) }}<br><br>
@stop
