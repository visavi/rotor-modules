@extends('layout')

@section('title', __('game::games.safe'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.safe') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    {{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}<br><br>

    {{ __('game::games.safe_dont_rush', ['name' => $user->getName()]) }}<br>
    <br><img src="/assets/modules/games/safe/safe-closed.png" alt="{{ __('game::games.safe_alt') }}"><br>

    {{ __('game::games.safe_ready') }}<br><br>

    {{ __('game::games.safe_combination') }}<br>
    <span class="badge bg-info">-</span>
    <span class="badge bg-info">-</span>
    <span class="badge bg-info">-</span>
    <span class="badge bg-info">-</span>
    <span class="badge bg-info">-</span>

    <div class="section-form mb-3 shadow">
        <form action="/games/safe/go" method="post">
            @csrf
            <div class="mb-3 row{{ hasError('code') }}">
                <div class="col-1">
                    <input class="form-control" name="code0" maxlength="1" value="{{ old('code0') }}" required>
                </div>
                <div class="col-1">
                    <input class="form-control" name="code1" maxlength="1" value="{{ old('code1') }}" required>
                </div>
                <div class="col-1">
                    <input class="form-control" name="code2" maxlength="1" value="{{ old('code2') }}" required>
                </div>
                <div class="col-1">
                    <input class="form-control" name="code3" maxlength="1" value="{{ old('code3') }}" required>
                </div>
                <div class="col-1">
                    <input class="form-control" name="code4" maxlength="1" value="{{ old('code4') }}" required>
                </div>
            </div>
            <button class="btn btn-primary">{{ __('game::games.safe_button') }}</button>
        </form>
    </div>

    {{ __('game::games.safe_try') }}<br>
    {{ __('game::games.safe_prize', ['money' => plural($prize, setting('moneyname'))]) }}<br>
    {{ __('game::games.safe_price', ['money' => plural($price, setting('moneyname'))]) }}<br>
    {{ __('game::games.safe_price_info') }}<br>
    {{ __('game::games.safe_attempts_info') }}<br>
    {{ __('game::games.safe_forward') }}<br>
@stop
