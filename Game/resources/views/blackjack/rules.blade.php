@extends('layout')

@section('title', __('game::games.rules'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item"><a href="/games/blackjack">{{ __('game::games.blackjack') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.rules') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    {!! __('game::games.bj_rules_bet', ['button' => '<b>' . __('game::games.play') . '</b>']) !!}<br>
    {{ __('game::games.bj_rules_dealing') }}<br>
    {{ __('game::games.bj_rules_players') }}<br><br>

    <b>{{ __('game::games.bj_rules_scores') }}</b>

    <div class="my-2">
        {{-- Двойка — десятка идут одной группой, дальше каждая фигура отдельно --}}
        @foreach ([3, 7, 11, 15, 19, 23, 27, 31, 35] as $card)
            <img src="/assets/modules/games/cards/{{ $card }}.png" alt="image">
        @endforeach

        <div>{{ __('game::games.bj_card_numbers') }}</div>
    </div>

    <div class="d-flex flex-wrap gap-3 my-2">
        @foreach (['jack' => 39, 'queen' => 43, 'king' => 47, 'ace' => 51] as $name => $card)
            <div>
                <img src="/assets/modules/games/cards/{{ $card }}.png" alt="image">
                <div>{{ __('game::games.bj_card_' . $name) }}</div>
            </div>
        @endforeach
    </div>
    <br>

    {{ __('game::games.bj_rules_suits') }}<br>
    {!! __('game::games.bj_rules_take', ['button' => '<b>' . __('game::games.bj_take_card') . '</b>']) !!}<br>
    {{ __('game::games.bj_rules_bust') }}<br>
    {{ __('game::games.bj_rules_priority') }}<br><br>

    {!! __('game::games.bj_rules_open', ['button' => '<b>' . __('game::games.bj_open') . '</b>']) !!}
    {{ __('game::games.bj_rules_winner') }}
    {{ __('game::games.bj_rules_draw') }}<br><br>

    @include('game::blackjack/_payouts')
@stop
