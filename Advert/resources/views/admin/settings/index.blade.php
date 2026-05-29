@extends('layout')

@section('title', __('advert::adverts.settings'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item active">{{ __('advert::adverts.settings') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="section-form mb-3 shadow">
        <form method="post" action="{{ route('advert.settings.update') }}">
            @csrf

            <div class="mb-3{{ hasError('sets[rekusershow]') }}">
                <label for="rekusershow" class="form-label">{{ __('advert::adverts.adverts_count_links') }}:</label>
                <input type="number" class="form-control" id="rekusershow" name="sets[rekusershow]" maxlength="2" value="{{ getInput('sets.rekusershow', $settings['rekusershow'] ?? 1) }}" required>
                <div class="invalid-feedback">{{ textError('sets[rekusershow]') }}</div>
            </div>

            <div class="mb-3{{ hasError('sets[rekuserprice]') }}">
                <label for="rekuserprice" class="form-label">{{ __('advert::adverts.adverts_price') }}:</label>
                <input type="number" class="form-control" id="rekuserprice" name="sets[rekuserprice]" maxlength="8" value="{{ getInput('sets.rekuserprice', $settings['rekuserprice'] ?? 1000) }}" required>
                <div class="invalid-feedback">{{ textError('sets[rekuserprice]') }}</div>
            </div>

            <div class="mb-3{{ hasError('sets[rekuserpoint]') }}">
                <label for="rekuserpoint" class="form-label">{{ __('advert::adverts.adverts_points') }}:</label>
                <input type="number" class="form-control" id="rekuserpoint" name="sets[rekuserpoint]" maxlength="3" value="{{ getInput('sets.rekuserpoint', $settings['rekuserpoint'] ?? 50) }}" required>
                <div class="invalid-feedback">{{ textError('sets[rekuserpoint]') }}</div>
            </div>

            <div class="mb-3{{ hasError('sets[rekuseroptprice]') }}">
                <label for="rekuseroptprice" class="form-label">{{ __('advert::adverts.adverts_option') }}:</label>
                <input type="number" class="form-control" id="rekuseroptprice" name="sets[rekuseroptprice]" maxlength="8" value="{{ getInput('sets.rekuseroptprice', $settings['rekuseroptprice'] ?? 100) }}" required>
                <div class="invalid-feedback">{{ textError('sets[rekuseroptprice]') }}</div>
            </div>

            <div class="mb-3{{ hasError('sets[rekusertime]') }}">
                <label for="rekusertime" class="form-label">{{ __('advert::adverts.adverts_term') }}:</label>
                <input type="number" class="form-control" id="rekusertime" name="sets[rekusertime]" maxlength="3" value="{{ getInput('sets.rekusertime', $settings['rekusertime'] ?? 12) }}" required>
                <div class="invalid-feedback">{{ textError('sets[rekusertime]') }}</div>
            </div>

            <div class="mb-3{{ hasError('sets[rekusertotal]') }}">
                <label for="rekusertotal" class="form-label">{{ __('advert::adverts.adverts_max_links') }}:</label>
                <input type="number" class="form-control" id="rekusertotal" name="sets[rekusertotal]" maxlength="2" value="{{ getInput('sets.rekusertotal', $settings['rekusertotal'] ?? 10) }}" required>
                <div class="invalid-feedback">{{ textError('sets[rekusertotal]') }}</div>
            </div>

            <div class="mb-3{{ hasError('sets[rekuserpost]') }}">
                <label for="rekuserpost" class="form-label">{{ __('advert::adverts.adverts_per_page') }}:</label>
                <input type="number" class="form-control" id="rekuserpost" name="sets[rekuserpost]" maxlength="2" value="{{ getInput('sets.rekuserpost', $settings['rekuserpost'] ?? 10) }}" required>
                <div class="invalid-feedback">{{ textError('sets[rekuserpost]') }}</div>
            </div>

            <button class="btn btn-primary">{{ __('main.save') }}</button>
        </form>
    </div>
@stop
