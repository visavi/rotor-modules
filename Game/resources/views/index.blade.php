@extends('layout')

@section('title', __('Game::games.module'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active">{{ __('Game::games.module') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <i class="far fa-circle fa-lg text-muted"></i> <a href="/games/blackjack">{{ __('Game::games.blackjack') }}</a><br>
    <i class="far fa-circle fa-lg text-muted"></i> <a href="/games/dices">{{ __('Game::games.dices') }}</a><br>
    <i class="far fa-circle fa-lg text-muted"></i> <a href="/games/thimbles">{{ __('Game::games.thimbles') }}</a><br>
    <i class="far fa-circle fa-lg text-muted"></i> <a href="/games/bandit">{{ __('Game::games.bandit') }}</a><br>
    <i class="far fa-circle fa-lg text-muted"></i> <a href="/games/guess">{{ __('Game::games.guess') }}</a><br>
    <i class="far fa-circle fa-lg text-muted"></i> <a href="/games/safe">{{ __('Game::games.safe') }}</a><br>
@stop
