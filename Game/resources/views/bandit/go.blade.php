@extends('layout')

@section('title', __('game::games.your_turn'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item"><a href="/games/bandit">{{ __('game::games.slot') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.your_turn') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <img src="/assets/modules/games/bandit/{{ $num[1] }}.gif" alt="image"> <img src="/assets/modules/games/bandit/{{ $num[2] }}.gif" alt="image"> <img src="/assets/modules/games/bandit/{{ $num[3] }}.gif" alt="image"><br>

    <img src="/assets/modules/games/bandit/{{ $num[4] }}.gif" alt="image"> <img src="/assets/modules/games/bandit/{{ $num[5] }}.gif" alt="image"> <img src="/assets/modules/games/bandit/{{ $num[6] }}.gif" alt="image"><br>

    <img src="/assets/modules/games/bandit/{{ $num[7] }}.gif" alt="image"> <img src="/assets/modules/games/bandit/{{ $num[8] }}.gif" alt="image"> <img src="/assets/modules/games/bandit/{{ $num[9] }}.gif" alt="image"><br><br>

    @if ($sum > 0)
        @foreach ($results as $result)
            {{ $result }}<br>
        @endforeach

        <i class="fas fa-trophy"></i> {{ __('game::games.win_amount', ['money' => plural($sum, setting('moneyname'))]) }}<br><br>
    @endif

    <a class="btn btn-primary" href="/games/bandit/go?rand={{ mt_rand(1000, 99999) }}">{{ __('game::games.play') }}</a><br><br>

    {{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}<br>
@stop
