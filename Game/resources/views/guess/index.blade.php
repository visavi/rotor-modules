@extends('layout')

@section('title', __('game::games.guess'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">{{ __('game::games.module') }}</a></li>
            <li class="breadcrumb-item active">{{ __('game::games.guess') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <b>{{ __('game::games.guess_enter_range') }}</b><br><br>

    <div class="section-form mb-3 shadow">
        <form action="/games/guess/go" method="post">
            @csrf
            <div class="mb-3{{ hasError('guess') }}">
                <label for="guess" class="form-label">{{ __('game::games.guess_enter') }}</label>
                <input class="form-control" name="guess" id="guess" value="{{ old('guess') }}" required>
                <div class="invalid-feedback">{{ textError('guess') }}</div>
            </div>

            <button class="btn btn-primary">{{ __('game::games.guess_button') }}</button>
        </form>
    </div>

    {{ __('game::games.balance', ['money' => plural($user->money, setting('moneyname'))]) }}<br><br>

    {{ __('game::games.guess_howto') }}<br>
    {{ __('game::games.guess_attempt_price', ['money' => plural(3, setting('moneyname'))]) }}<br>
    {{ __('game::games.guess_hint_info') }}<br>
    {{ __('game::games.guess_restart_info') }}<br>
    {{ __('game::games.guess_prize', ['money' => plural(100, setting('moneyname'))]) }}<br>
    {{ __('game::games.lets_go') }}<br>
@stop
