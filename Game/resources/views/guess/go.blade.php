@extends('layout')

@section('title', 'Ваш ход')

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">Игры / Развлечения</a></li>
            <li class="breadcrumb-item"><a href="/games/guess">Угадай число</a></li>
            <li class="breadcrumb-item active">Ваш ход</li>
        </ol>
    </nav>
@stop

@section('content')
    @if ($guessNumber !== $guess['number'])

        @if ($guess['count'] < 5)
            <span class="badge badge-info">{{ $guessNumber }}</span> — {!! $hint !!}<br><br>

            Использовано попыток: <b>{{ $guess['count'] }} из 5</b><br><br>
        @else
            <i class="fa fa-times"></i> <b class="text-danger">Поражение! Вы не угадали число!</b><br>
            Было загадано: {{ $guess['number'] }}<br><br>

            <b>Начните новую игру</b><br>
        @endif
    @else
        <b class="text-success">Поздравляем!!! Вы угадали число: {{ $guess['number'] }}</b><br>
        Ваш выигрыш составил {{ plural(100, setting('moneyname')) }}<br><br>

        <b>Начните новую игру</b><br>
    @endif

    <b>Введите число от 1 до 100</b><br>
    <div class="section-form mb-3 shadow">
        <form action="/games/guess/go" method="post">
            @csrf
            <div class="form-group{{ hasError('guess') }}">
                <label for="guess">Введите число:</label>
                <input class="form-control" name="guess" id="guess" value="{{ getInput('guess') }}" required>
                <div class="invalid-feedback">{{ textError('guess') }}</div>
            </div>

            <button class="btn btn-primary">Угадать</button>
        </form>
    </div>

    У вас в наличии: {{ plural($user->money, setting('moneyname')) }}<br>

    <i class="fa fa-arrow-circle-up"></i> <a href="/games/guess?new=1">Начать заново</a><br>
@stop
