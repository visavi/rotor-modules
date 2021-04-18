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
    <div class="container">
        <div class="row">
            <div class="col-md-4 col-sm-6">
                <div class="section my-3 shadow">
                    <i class="fas fa-coins fa-5x"></i>
                    <a href="/games/blackjack" class="h5">{{ __('Game::games.blackjack') }}</a>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="section my-3 shadow">
                    <i class="fas fa-dice fa-5x"></i>
                    <a href="/games/dices" class="h5">{{ __('Game::games.dices') }}</a>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="section my-3 shadow">
                    <i class="fas fa-beer fa-5x"></i>
                    <a href="/games/thimbles" class="h5">{{ __('Game::games.thimbles') }}</a>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="section my-3 shadow">
                    <i class="fas fa-sort-numeric-up-alt fa-5x"></i>
                    <a href="/games/guess" class="h5">{{ __('Game::games.guess') }}</a>

                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="section my-3 shadow">
                    <i class="fas fa-dollar-sign fa-5x"></i>
                    <a href="/games/bandit" class="h5">{{ __('Game::games.slot') }}</a>
                </div>
            </div>
            <div class="col-md-4 col-sm-6">
                <div class="section my-3 shadow">
                    <i class="fas fa-piggy-bank fa-5x"></i>
                    <a href="/games/safe" class="h5">{{ __('Game::games.safe') }}</a>
                </div>
            </div>
        </div>
    </div>
@stop
