@extends('layout')

@section('title', __('board::boards.settings'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}">{{ __('index.modules') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.module', ['module' => 'Board']) }}">{{ __('admin.modules.module') }} {{ __('board::boards.boards_section') }}</a></li>
            <li class="breadcrumb-item active">{{ __('board::boards.settings') }}</li>
        </ol>
    </nav>
@stop

@section('header')
    <h1>{{ __('board::boards.settings') }}</h1>
@stop

@section('content')
<form method="post" action="{{ route('board.settings.update') }}">
    @csrf
    <div class="form-check mb-3">
        <input type="hidden" value="0" name="sets[boards_create]">
        <input type="checkbox" class="form-check-input" value="1" name="sets[boards_create]" id="boards_create"{{ getInput('sets.boards_create', $settings['boards_create']) ? ' checked' : '' }}>
        <label class="form-check-label" for="boards_create">{{ __('board::boards.settings_boards_create') }}</label>
    </div>

    <div class="mb-3{{ hasError('sets[boards_period]') }}">
        <label for="boards_period" class="form-label">{{ __('board::boards.settings_boards_period') }}:</label>
        <input type="number" class="form-control" id="boards_period" name="sets[boards_period]" min="1" value="{{ getInput('sets.boards_period', $settings['boards_period']) }}" required>
        <div class="invalid-feedback">{{ textError('sets[boards_period]') }}</div>
    </div>

    <div class="mb-3{{ hasError('sets[boards_per_page]') }}">
        <label for="boards_per_page" class="form-label">{{ __('board::boards.settings_boards_per_page') }}:</label>
        <input type="number" class="form-control" id="boards_per_page" name="sets[boards_per_page]" maxlength="2" value="{{ getInput('sets.boards_per_page', $settings['boards_per_page']) }}" required>
        <div class="invalid-feedback">{{ textError('sets[boards_per_page]') }}</div>
    </div>

    <div class="mb-3">
        <label for="board_title_min" class="form-label">{{ __('board::boards.settings_board_title_length') }}:</label>
        <div class="d-flex gap-2">
            <input type="number" class="form-control{{ hasError('sets[board_title_min]') }}" id="board_title_min" name="sets[board_title_min]" value="{{ old('sets.board_title_min', $settings['board_title_min']) }}" placeholder="{{ __('main.min') }}" required>
            <input type="number" class="form-control{{ hasError('sets[board_title_max]') }}" name="sets[board_title_max]" value="{{ old('sets.board_title_max', $settings['board_title_max']) }}" placeholder="{{ __('main.max') }}" required>
        </div>
        <div class="invalid-feedback d-block">
            <div>{{ textError('sets[board_title_min]') }}</div>
            <div>{{ textError('sets[board_title_max]') }}</div>
        </div>
    </div>

    <div class="mb-3">
        <label for="board_text_min" class="form-label">{{ __('board::boards.settings_board_text_length') }}:</label>
        <div class="d-flex gap-2">
            <input type="number" class="form-control{{ hasError('sets[board_text_min]') }}" id="board_text_min" name="sets[board_text_min]" value="{{ old('sets.board_text_min', $settings['board_text_min']) }}" placeholder="{{ __('main.min') }}" required>
            <input type="number" class="form-control{{ hasError('sets[board_text_max]') }}" name="sets[board_text_max]" value="{{ old('sets.board_text_max', $settings['board_text_max']) }}" placeholder="{{ __('main.max') }}" required>
        </div>
        <div class="invalid-feedback d-block">
            <div>{{ textError('sets[board_text_min]') }}</div>
            <div>{{ textError('sets[board_text_max]') }}</div>
        </div>
    </div>

    <div class="mb-3">
        <label for="board_category_min" class="form-label">{{ __('board::boards.settings_board_category_length') }}:</label>
        <div class="d-flex gap-2">
            <input type="number" class="form-control{{ hasError('sets[board_category_min]') }}" id="board_category_min" name="sets[board_category_min]" value="{{ old('sets.board_category_min', $settings['board_category_min']) }}" placeholder="{{ __('main.min') }}" required>
            <input type="number" class="form-control{{ hasError('sets[board_category_max]') }}" name="sets[board_category_max]" value="{{ old('sets.board_category_max', $settings['board_category_max']) }}" placeholder="{{ __('main.max') }}" required>
        </div>
        <div class="invalid-feedback d-block">
            <div>{{ textError('sets[board_category_min]') }}</div>
            <div>{{ textError('sets[board_category_max]') }}</div>
        </div>
    </div>

    <div class="form-check mb-3">
        <input type="hidden" value="0" name="sets[feed_items_show]">
        <input type="checkbox" class="form-check-input" value="1" name="sets[feed_items_show]" id="feed_items_show"{{ ! empty($settings['feed_items_show']) ? ' checked' : '' }}>
        <label class="form-check-label" for="feed_items_show">{{ __('board::boards.feed_items_show') }}</label>
    </div>

    <button class="btn btn-primary">{{ __('main.save') }}</button>
</form>
@stop
