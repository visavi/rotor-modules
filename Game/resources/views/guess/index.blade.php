@extends('layout')

@section('title')
    Угадай число
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/games">Игры / Развлечения</a></li>
            <li class="breadcrumb-item active">Угадай число</li>
        </ol>
    </nav>
@stop

@section('content')
    <b>Введите число от 1 до 100</b><br><br>

    <div class="section-form p-2 shadow">
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

    У вас в наличии: {{ plural($user->money, setting('moneyname')) }}<br><br>

    Для участия в игре введите число и нажмите "Угадать"<br>
    За каждую попытку у вас будут списывать по {{ plural(3, setting('moneyname')) }}<br>
    После каждой попытки вам дают подсказку большое это число или маленькое<br>
    Если вы не уложились за 5 попыток, то игра будет начата заново<br>
    При выигрыше вы получаете {{ plural(100, setting('moneyname')) }}<br>
    Итак дерзайте!<br>
@stop
