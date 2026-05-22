@extends('layout')

@section('title', __('news::news.settings'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item active">{{ __('news::news.settings') }}</li>
        </ol>
    </nav>
@stop

@section('content')
<form action="{{ route('news.settings.update') }}" method="post">
    @csrf
    <div class="mb-3{{ hasError('sets[postnews]') }}">
        <label for="postnews" class="form-label">{{ __('settings.news_per_page') }}:</label>
        <input type="number" class="form-control" id="postnews" name="sets[postnews]" maxlength="2" value="{{ getInput('sets.postnews', $settings['postnews']) }}" required>
        <div class="invalid-feedback">{{ textError('sets[postnews]') }}</div>
    </div>

    <div class="mb-3">
        <label for="news_title_min" class="form-label">{{ __('settings.news_title_length') }}:</label>
        <div class="d-flex gap-2">
            <input type="number" class="form-control{{ hasError('sets[news_title_min]') }}" id="news_title_min" name="sets[news_title_min]" value="{{ old('sets.news_title_min', $settings['news_title_min']) }}" placeholder="{{ __('main.min') }}" required>
            <input type="number" class="form-control{{ hasError('sets[news_title_max]') }}" name="sets[news_title_max]" value="{{ old('sets.news_title_max', $settings['news_title_max']) }}" placeholder="{{ __('main.max') }}" required>
        </div>
        <div class="invalid-feedback d-block">
            <div>{{ textError('sets[news_title_min]') }}</div>
            <div>{{ textError('sets[news_title_max]') }}</div>
        </div>
    </div>

    <div class="mb-3">
        <label for="news_text_min" class="form-label">{{ __('settings.news_text_length') }}:</label>
        <div class="d-flex gap-2">
            <input type="number" class="form-control{{ hasError('sets[news_text_min]') }}" id="news_text_min" name="sets[news_text_min]" value="{{ old('sets.news_text_min', $settings['news_text_min']) }}" placeholder="{{ __('main.min') }}" required>
            <input type="number" class="form-control{{ hasError('sets[news_text_max]') }}" name="sets[news_text_max]" value="{{ old('sets.news_text_max', $settings['news_text_max']) }}" placeholder="{{ __('main.max') }}" required>
        </div>
        <div class="invalid-feedback d-block">
            <div>{{ textError('sets[news_text_min]') }}</div>
            <div>{{ textError('sets[news_text_max]') }}</div>
        </div>
    </div>

    <div class="form-check mb-3">
        <input type="hidden" value="0" name="sets[feed_news_show]">
        <input type="checkbox" class="form-check-input" value="1" name="sets[feed_news_show]" id="feed_news_show"{{ getInput('sets.feed_news_show', $settings['feed_news_show']) ? ' checked' : '' }}>
        <label class="form-check-label" for="feed_news_show">{{ __('settings.feed_news_show') }}</label>
    </div>

    <div class="mb-3{{ hasError('sets[feed_news_rating]') }}">
        <label for="feed_news_rating" class="form-label">{{ __('settings.feed_news_rating') }}:</label>
        <input type="number" class="form-control" id="feed_news_rating" name="sets[feed_news_rating]" maxlength="2" value="{{ getInput('sets.feed_news_rating', $settings['feed_news_rating']) }}" required>
        <div class="invalid-feedback">{{ textError('sets[feed_news_rating]') }}</div>
    </div>

    <button class="btn btn-primary">{{ __('main.save') }}</button>
</form>

@stop
