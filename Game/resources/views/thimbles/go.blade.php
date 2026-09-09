@extends('layout')

@section('title', __('game::games.thimbles_game'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item"><a href="/games/thimbles">{{ __('game::games.thimbles') }}</a></li>
            <li class="breadcrumb-item"><a href="/games/thimbles/choice">{{ __('game::games.thimbles_choice') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.thimbles_game') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <a href="/games/thimbles/go?thimble=1&amp;rand={{ mt_rand(1000, 99999) }}"><img src="/assets/modules/games/thimbles/{{ $randThimble === 1 ? 'thimble-ball' : 'thimble' }}.svg" width="86" height="74" alt="{{ __('game::games.thimbles') }}"></a>
    <a href="/games/thimbles/go?thimble=2&amp;rand={{ mt_rand(1000, 99999) }}"><img src="/assets/modules/games/thimbles/{{ $randThimble === 2 ? 'thimble-ball' : 'thimble' }}.svg" width="86" height="74" alt="{{ __('game::games.thimbles') }}"></a>
    <a href="/games/thimbles/go?thimble=3&amp;rand={{ mt_rand(1000, 99999) }}"><img src="/assets/modules/games/thimbles/{{ $randThimble === 3 ? 'thimble-ball' : 'thimble' }}.svg" width="86" height="74" alt="{{ __('game::games.thimbles') }}"></a><br><br>

    {{ __('game::games.thimbles_pick') }}<br><br>

    <div class="fw-bold">
        <i class="fas fa-trophy"></i> {!! $result !!}
    </div>

    {{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}<br><br>
@stop
