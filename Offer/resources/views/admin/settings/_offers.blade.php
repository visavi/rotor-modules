@extends('layout')

@section('header')
    <h1>{{ __('offer::offers.settings') }}</h1>
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}">{{ __('index.modules') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.module', ['module' => 'Offer']) }}">{{ __('offer::offers.section') }}</a></li>
            <li class="breadcrumb-item active">{{ __('offer::offers.settings') }}</li>
        </ol>
    </nav>
@stop

@section('content')
<form method="post">
    @csrf
    <div class="mb-3{{ hasError('sets[postoffers]') }}">
        <label for="postoffers" class="form-label">{{ __('offer::offers.offers_per_page') }}:</label>
        <input type="number" class="form-control" id="postoffers" name="sets[postoffers]" maxlength="2" value="{{ getInput('sets.postoffers', $settings['postoffers']) }}" required>
        <div class="invalid-feedback">{{ textError('sets[postoffers]') }}</div>
    </div>

    <div class="mb-3{{ hasError('sets[addofferspoint]') }}">
        <label for="addofferspoint" class="form-label">{{ __('offer::offers.offers_points') }}:</label>
        <input type="number" class="form-control" id="addofferspoint" name="sets[addofferspoint]" maxlength="4" value="{{ getInput('sets.addofferspoint', $settings['addofferspoint']) }}" required>
        <div class="invalid-feedback">{{ textError('sets[addofferspoint]') }}</div>
    </div>

    <div class="mb-3">
        <label for="offer_title_min" class="form-label">{{ __('offer::offers.offer_title_length') }}:</label>
        <div class="d-flex gap-2">
            <input type="number" class="form-control{{ hasError('sets[offer_title_min]') }}" id="offer_title_min" name="sets[offer_title_min]" value="{{ old('sets.offer_title_min', $settings['offer_title_min']) }}" placeholder="{{ __('main.min') }}" required>
            <input type="number" class="form-control{{ hasError('sets[offer_title_max]') }}" name="sets[offer_title_max]" value="{{ old('sets.offer_title_max', $settings['offer_title_max']) }}" placeholder="{{ __('main.max') }}" required>
        </div>
        <div class="invalid-feedback d-block">
            <div>{{ textError('sets[offer_title_min]') }}</div>
            <div>{{ textError('sets[offer_title_max]') }}</div>
        </div>
    </div>

    <div class="mb-3">
        <label for="offer_text_min" class="form-label">{{ __('offer::offers.offer_text_length') }}:</label>
        <div class="d-flex gap-2">
            <input type="number" class="form-control{{ hasError('sets[offer_text_min]') }}" id="offer_text_min" name="sets[offer_text_min]" value="{{ old('sets.offer_text_min', $settings['offer_text_min']) }}" placeholder="{{ __('main.min') }}" required>
            <input type="number" class="form-control{{ hasError('sets[offer_text_max]') }}" name="sets[offer_text_max]" value="{{ old('sets.offer_text_max', $settings['offer_text_max']) }}" placeholder="{{ __('main.max') }}" required>
        </div>
        <div class="invalid-feedback d-block">
            <div>{{ textError('sets[offer_text_min]') }}</div>
            <div>{{ textError('sets[offer_text_max]') }}</div>
        </div>
    </div>

    <div class="mb-3">
        <label for="offer_reply_min" class="form-label">{{ __('offer::offers.offer_reply_length') }}:</label>
        <div class="d-flex gap-2">
            <input type="number" class="form-control{{ hasError('sets[offer_reply_min]') }}" id="offer_reply_min" name="sets[offer_reply_min]" value="{{ old('sets.offer_reply_min', $settings['offer_reply_min']) }}" placeholder="{{ __('main.min') }}" required>
            <input type="number" class="form-control{{ hasError('sets[offer_reply_max]') }}" name="sets[offer_reply_max]" value="{{ old('sets.offer_reply_max', $settings['offer_reply_max']) }}" placeholder="{{ __('main.max') }}" required>
        </div>
        <div class="invalid-feedback d-block">
            <div>{{ textError('sets[offer_reply_min]') }}</div>
            <div>{{ textError('sets[offer_reply_max]') }}</div>
        </div>
    </div>

    <div class="form-check mb-3">
        <input type="hidden" value="0" name="sets[feed_offers_show]">
        <input type="checkbox" class="form-check-input" value="1" name="sets[feed_offers_show]" id="feed_offers_show"{{ ! empty($settings['feed_offers_show']) ? ' checked' : '' }}>
        <label class="form-check-label" for="feed_offers_show">{{ __('offer::offers.feed_offers_show') }}</label>
    </div>

    <div class="mb-3">
        <label for="feed_offers_rating" class="form-label">{{ __('offer::offers.feed_offers_rating') }}:</label>
        <input type="number" class="form-control" id="feed_offers_rating" name="sets[feed_offers_rating]" maxlength="2" value="{{ getInput('sets.feed_offers_rating', $settings['feed_offers_rating'] ?? -5) }}">
    </div>

    <button class="btn btn-primary">{{ __('main.save') }}</button>
</form>
@stop
