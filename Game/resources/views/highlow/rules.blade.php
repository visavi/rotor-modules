@extends('layout')

@section('title', __('game::games.highlow') . ' - ' . __('game::games.rules'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item"><a href="/games/highlow">{{ __('game::games.highlow') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.rules') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    {{ __('game::games.hl_intro') }}<br><br>

    <ul>
        <li>{{ __('game::games.hl_rules_order') }}</li>
        <li>{{ __('game::games.hl_rules_deck') }}</li>
        <li>{{ __('game::games.hl_rules_draw') }}</li>
        <li>{{ __('game::games.hl_rules_price') }}</li>
        <li>{{ __('game::games.hl_rules_cash') }}</li>
    </ul>

    <i class="fa fa-coins"></i> <a href="/games/highlow">{{ __('game::games.highlow') }}</a>
@stop
