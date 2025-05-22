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
    У вас в наличии: {{ plural($user->money, setting('moneyname')) }}<br><br>

    {{ $user->getName() }}, не торопись! Просто хорошо подумай<br>
    <br><img src="/assets/modules/games/safe/safe-closed.png" alt="сейф"><br>

    Всё готово для совершения взлома! Введите комбинацию цифр и нажмите ломать сейф!<br><br>

    Комбинация сейфа:<br>
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
                    <input class="form-control" name="code0" maxlength="1" value="{{ getInput('code0') }}" required>
                </div>
                <div class="col-1">
                    <input class="form-control" name="code1" maxlength="1" value="{{ getInput('code1') }}" required>
                </div>
                <div class="col-1">
                    <input class="form-control" name="code2" maxlength="1" value="{{ getInput('code2') }}" required>
                </div>
                <div class="col-1">
                    <input class="form-control" name="code3" maxlength="1" value="{{ getInput('code3') }}" required>
                </div>
                <div class="col-1">
                    <input class="form-control" name="code4" maxlength="1" value="{{ getInput('code4') }}" required>
                </div>
            </div>
            <button class="btn btn-primary">Ломать сейф</button>
        </form>
    </div>

    Попробуй вскрыть наш сейф.<br>
    В сейфе тебя ждёт: {{ plural(1000, setting('moneyname')) }}<br>
    За попытку взлома ты заплатишь {{ plural(100, setting('moneyname')) }}<br>
    Платишь 1 paз зa 5 попыток. Ну это чтобы купить себе необходимое для взлома оборудование.<br>
    У тебя будет только 5 попыток чтобы подобрать код из 5-х цифр.<br>
    Если тебя это устраивает, то ВПЕРЁД!<br>
@stop
