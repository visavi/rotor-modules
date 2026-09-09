@extends('layout')

@section('title', __('game::games.thimbles_choice'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item"><a href="/games/thimbles">{{ __('game::games.thimbles') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.thimbles_choice') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <a href="/games/thimbles/go?thimble=1&amp;rand={{ mt_rand(1000, 99999) }}"><img src="/assets/modules/games/thimbles/thimble.svg" width="86" height="74" alt="{{ __('game::games.thimbles') }}"></a>
    <a href="/games/thimbles/go?thimble=2&amp;rand={{ mt_rand(1000, 99999) }}"><img src="/assets/modules/games/thimbles/thimble.svg" width="86" height="74" alt="{{ __('game::games.thimbles') }}"></a>
    <a href="/games/thimbles/go?thimble=3&amp;rand={{ mt_rand(1000, 99999) }}"><img src="/assets/modules/games/thimbles/thimble.svg" width="86" height="74" alt="{{ __('game::games.thimbles') }}"></a><br><br>

    {{ __('game::games.thimbles_pick') }}<br>

    {{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}<br><br>
@stop
