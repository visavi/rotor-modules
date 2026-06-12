@extends('layout')

@section('title', __('photo::photos.settings'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}">{{ __('index.modules') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.module', ['module' => 'Photo']) }}">{{ __('admin.modules.module') }} {{ __('photo::photos.photos') }}</a></li>
            <li class="breadcrumb-item active">{{ __('photo::photos.settings') }}</li>
        </ol>
    </nav>
@stop

@section('header')
    <h1>{{ __('photo::photos.settings') }}</h1>
@stop

@section('content')
<form method="post" action="{{ route('photo.settings.update') }}">
    @csrf
    <div class="mb-3{{ hasError('sets[fotolist]') }}">
        <label for="fotolist" class="form-label">{{ __('photo::photos.settings_photos_per_page') }}:</label>
        <input type="number" class="form-control" id="fotolist" name="sets[fotolist]" maxlength="2" value="{{ getInput('sets.fotolist', $settings['fotolist']) }}" required>
        <div class="invalid-feedback">{{ textError('sets[fotolist]') }}</div>
    </div>

    <div class="mb-3{{ hasError('sets[photogroup]') }}">
        <label for="photogroup" class="form-label">{{ __('photo::photos.settings_photos_groups') }}:</label>
        <input type="number" class="form-control" id="photogroup" name="sets[photogroup]" maxlength="2" value="{{ getInput('sets.photogroup', $settings['photogroup']) }}" required>
        <div class="invalid-feedback">{{ textError('sets[photogroup]') }}</div>
    </div>

    <div class="form-check mb-3">
        <input type="hidden" value="0" name="sets[photos_create]">
        <input type="checkbox" class="form-check-input" value="1" name="sets[photos_create]" id="photos_create"{{ getInput('sets.photos_create', $settings['photos_create']) ? ' checked' : '' }}>
        <label class="form-check-label" for="photos_create">{{ __('photo::photos.settings_photos_create') }}</label>
    </div>

    <div class="mb-3">
        <label for="photo_title_min" class="form-label">{{ __('photo::photos.settings_photo_title_length') }}:</label>
        <div class="d-flex gap-2">
            <input type="number" class="form-control{{ hasError('sets[photo_title_min]') }}" id="photo_title_min" name="sets[photo_title_min]" value="{{ old('sets.photo_title_min', $settings['photo_title_min']) }}" placeholder="{{ __('main.min') }}" required>
            <input type="number" class="form-control{{ hasError('sets[photo_title_max]') }}" name="sets[photo_title_max]" value="{{ old('sets.photo_title_max', $settings['photo_title_max']) }}" placeholder="{{ __('main.max') }}" required>
        </div>
        <div class="invalid-feedback d-block">
            <div>{{ textError('sets[photo_title_min]') }}</div>
            <div>{{ textError('sets[photo_title_max]') }}</div>
        </div>
    </div>

    <div class="mb-3">
        <label for="photo_text_min" class="form-label">{{ __('photo::photos.settings_photo_text_length') }}:</label>
        <div class="d-flex gap-2">
            <input type="number" class="form-control{{ hasError('sets[photo_text_min]') }}" id="photo_text_min" name="sets[photo_text_min]" value="{{ old('sets.photo_text_min', $settings['photo_text_min']) }}" placeholder="{{ __('main.min') }}" required>
            <input type="number" class="form-control{{ hasError('sets[photo_text_max]') }}" name="sets[photo_text_max]" value="{{ old('sets.photo_text_max', $settings['photo_text_max']) }}" placeholder="{{ __('main.max') }}" required>
        </div>
        <div class="invalid-feedback d-block">
            <div>{{ textError('sets[photo_text_min]') }}</div>
            <div>{{ textError('sets[photo_text_max]') }}</div>
        </div>
    </div>

    <div class="form-check mb-3">
        <input type="hidden" value="0" name="sets[feed_photos_show]">
        <input type="checkbox" class="form-check-input" value="1" name="sets[feed_photos_show]" id="feed_photos_show"{{ ! empty($settings['feed_photos_show']) ? ' checked' : '' }}>
        <label class="form-check-label" for="feed_photos_show">{{ __('photo::photos.feed_photos_show') }}</label>
    </div>

    <div class="mb-3{{ hasError('sets[feed_photos_rating]') }}">
        <label for="feed_photos_rating" class="form-label">{{ __('photo::photos.feed_photos_rating') }}:</label>
        <input type="number" class="form-control" id="feed_photos_rating" name="sets[feed_photos_rating]" maxlength="2" value="{{ getInput('sets.feed_photos_rating', $settings['feed_photos_rating']) }}" required>
        <div class="invalid-feedback">{{ textError('sets[feed_photos_rating]') }}</div>
    </div>

    <button class="btn btn-primary">{{ __('main.save') }}</button>
</form>
@stop
