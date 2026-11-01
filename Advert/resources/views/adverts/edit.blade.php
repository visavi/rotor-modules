@extends('layout')

@section('title', __('advert::adverts.edit_advert'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/adverts">{{ __('index.advertising') }}</a></li>
            <li class="breadcrumb-item active">{{ __('advert::adverts.edit_advert') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="section-form mb-3 shadow">
        <form method="post" action="/adverts/edit/{{ $link->id }}">
            @csrf
            <div class="mb-3{{ hasError('site') }}">
                <label for="site" class="form-label">{{ __('advert::adverts.link') }}:</label>
                <input name="site" class="form-control" id="site" maxlength="100" value="{{ old('site', $link->site) }}" required>
                <div class="invalid-feedback">{{ textError('site') }}</div>
            </div>

            <div class="mb-3{{ hasError('name') }}">
                <label for="name" class="form-label">{{ __('advert::adverts.name') }}:</label>
                <input name="name" class="form-control" id="name" maxlength="35" value="{{ old('name', $link->name) }}" required>
                <div class="invalid-feedback">{{ textError('name') }}</div>
            </div>

            {{-- Опции оплачены при покупке: оплаченный цвет можно сменить, остальное только видно --}}
            @if ($link->color)
                <?php $color = old('color', $link->color); ?>
                <div class="col-sm-4 mb-3{{ hasError('color') }}">
                    <label for="color" class="form-label">{{ __('advert::adverts.color') }}:</label>
                    <div class="input-group">
                        <input type="text" name="color" class="form-control colorpicker" id="color" maxlength="7" value="{{ $color }}" required>
                        <input type="color" class="form-control form-control-color colorpicker-addon" value="{{ $color }}">
                    </div>
                    <div class="invalid-feedback">{{ textError('color') }}</div>
                </div>
            @else
                <div class="col-sm-4 mb-3">
                    <label for="color" class="form-label">{{ __('advert::adverts.color') }}:</label>
                    <input type="text" class="form-control" id="color" disabled>
                </div>
            @endif

            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="bold"{{ $link->bold ? ' checked' : '' }} disabled>
                <label class="form-check-label" for="bold">{{ __('advert::adverts.bold') }}</label>
            </div>

            <div class="form-text mb-3">{{ __('advert::adverts.options_locked') }}</div>

            <button class="btn btn-primary">{{ __('main.change') }}</button>
        </form>
    </div>

    <div class="text-muted fst-italic">
        {{ __('advert::adverts.expires') }}: {{ dateFixed($link->deleted_at) }}
    </div>
@stop
