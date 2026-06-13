@extends('layout')

@section('title', __('payment::payments.paid_adverts.edit_advert'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/payments/my">{{ __('payment::payments.paid_adverts.my_title') }}</a></li>
            <li class="breadcrumb-item active">{{ __('payment::payments.paid_adverts.edit_advert') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="alert alert-success">
        {{ __('payment::payments.paid_adverts.expires') }}: {{ dateFixed($advert->deleted_at) }}
    </div>

    <div class="section-form mb-3 shadow">
        <form action="/payments/my/edit/{{ $advert->id }}" method="post">
            @csrf

            <div class="mb-3">
                <label class="form-label">{{ __('payment::payments.paid_adverts.place') }}:</label>
                <input class="form-control" type="text" value="{{ $advert->getPlaceName() }}" disabled>
            </div>

            <div class="mb-3">
                <label for="site" class="form-label">{{ __('payment::payments.paid_adverts.link') }}:</label>
                <input class="form-control{{ hasError('site') }}" id="site" name="site" type="text" value="{{ old('site', $advert->site) }}" maxlength="100" placeholder="https://" required>
                <div class="invalid-feedback">{{ textError('site') }}</div>
            </div>

            <div class="mb-3">
                @php $names = (array) old('names', $advert->names) @endphp

                <label class="form-label">{{ __('payment::payments.paid_adverts.names') }}:</label>
                @for ($i = 0; $i < count($advert->names); $i++)
                    <input type="text" name="names[]" class="form-control{{ hasError('names.' . $i) }}{{ $i > 0 ? ' mt-1' : '' }}" value="{{ $names[$i] ?? '' }}" maxlength="35" placeholder="{{ __('payment::payments.paid_adverts.name') }}" required>
                    <div class="invalid-feedback">{{ textError('names.' . $i) }}</div>
                @endfor
            </div>

            <div class="col-sm-4 mb-3">
                <label for="color" class="form-label">{{ __('payment::payments.paid_adverts.color') }}@if (! $advert->color) <small class="text-muted">({{ __('payment::payments.paid_adverts.option_not_paid') }})</small>@endif:</label>
                <div class="input-group">
                    <input type="text" name="color" class="form-control{{ hasError('color') }} colorpicker" id="color" maxlength="7" value="{{ old('color', $advert->color) }}"{{ $advert->color ? '' : ' disabled' }}>
                    <input type="color" class="form-control form-control-color colorpicker-addon" value="{{ $advert->color ?: '#000000' }}"{{ $advert->color ? '' : ' disabled' }}>
                    <div class="invalid-feedback">{{ textError('color') }}</div>
                </div>
            </div>

            <div class="form-check form-switch mb-3">
                <input type="hidden" value="0" name="bold">
                <input type="checkbox" class="form-check-input" value="1" name="bold" id="bold"{{ old('bold', $advert->bold) ? ' checked' : '' }}{{ $advert->bold ? '' : ' disabled' }}>
                <label class="form-check-label" for="bold">{{ __('payment::payments.paid_adverts.bold') }}@if (! $advert->bold) <small class="text-muted">({{ __('payment::payments.paid_adverts.option_not_paid') }})</small>@endif</label>
            </div>

            <button class="btn btn-primary">{{ __('main.save') }}</button>
        </form>
    </div>
@stop
