@use('App\Models\User')
@use('Modules\HomeBuilder\Models\Block')
@extends('layout')

@php($pageTitle = $block->exists ? __('home_builder::home_builder.edit_block') : __('home_builder::home_builder.add_block_title'))

@section('title', $pageTitle)

@section('header')
    <h1>{{ $pageTitle }}</h1>
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.home-builder.index') }}">{{ __('home_builder::home_builder.title') }}</a></li>
            <li class="breadcrumb-item active">{{ $pageTitle }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="section-form mb-3 shadow">
        <form action="{{ $block->exists ? route('admin.home-builder.edit', ['id' => $block->id]) : route('admin.home-builder.create', ['type' => $block->type]) }}" method="post">
            @csrf
            <div class="mb-3">
                {{ __('home_builder::home_builder.type') }}: <strong>{{ $block->getTypeLabel() }}</strong>
            </div>

            <div class="mb-3{{ hasError('title') }}">
                <label for="title" class="form-label">{{ __('home_builder::home_builder.block_title') }}:</label>
                <input type="text" class="form-control" id="title" name="title" maxlength="100" value="{{ old('title', $block->title) }}">
                <div class="invalid-feedback">{{ textError('title') }}</div>
                <div class="form-text">{{ __('home_builder::home_builder.block_title_hint') }}</div>
            </div>

            @switch ($block->type)
                @case (Block::TEXT)
                    <div class="mb-3{{ hasError('text') }}">
                        <label for="text" class="form-label">{{ __('main.text') }}:</label>
                        <textarea class="form-control tiptap" maxlength="10000" id="text" rows="10" name="text" required>{{ old('text', $block->text) }}</textarea>
                        <div class="invalid-feedback">{{ textError('text') }}</div>
                        <span class="js-textarea-counter"></span>
                    </div>
                    @break

                @case (Block::HTML)
                    <div class="mb-3{{ hasError('code') }}">
                        <label for="code" class="form-label">{{ __('home_builder::home_builder.code') }}:</label>
                        <textarea class="form-control font-monospace" maxlength="65000" id="code" rows="15" name="code" spellcheck="false" required>{{ old('code', $block->code) }}</textarea>
                        <div class="invalid-feedback">{{ textError('code') }}</div>
                        <div class="form-text">{{ __('home_builder::home_builder.code_hint') }}</div>
                    </div>
                    @break

                @case (Block::TEMPLATE)
                    @include('home_builder::admin/_template')
                    @break

                @case (Block::ENTRIES)
                    @php($sources = Block::sourceList())
                    @php($inputSources = (array) old('sources', $block->sources ?? []))
                    @php($inputLayout = old('layout', $block->layoutOption()))
                    @php($canCustomize = $block->exists && isAdmin(User::BOSS) && in_array($block->layout, Block::LAYOUTS, true))

                    <div class="mb-3{{ hasError('sources') }}">
                        <div class="form-label">{{ __('home_builder::home_builder.sources') }}:</div>
                        @foreach ($sources as $source => $label)
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" value="{{ $source }}" name="sources[]" id="source_{{ $source }}"{{ in_array($source, $inputSources, true) ? ' checked' : '' }}>
                                <label for="source_{{ $source }}" class="form-check-label">{{ $label }}</label>
                            </div>
                        @endforeach
                        <div class="invalid-feedback">{{ textError('sources') }}</div>
                        <div class="form-text">{{ __('home_builder::home_builder.sources_hint') }}</div>
                    </div>

                    <div class="mb-3{{ hasError('layout') }}">
                        <label for="layout" class="form-label">{{ __('home_builder::home_builder.layout') }}:</label>
                        <div class="input-group">
                            <select class="form-select" id="layout" name="layout">
                                @foreach (Block::layoutOptions() as $value => $label)
                                    <option value="{{ $value }}"{{ $inputLayout === $value ? ' selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>

                            {{-- Кнопка отправляет отдельную форму customize-form под формой блока --}}
                            @if ($canCustomize)
                                <button class="btn btn-outline-primary" form="customize-form">{{ __('home_builder::home_builder.customize') }}</button>
                            @endif
                        </div>
                        <div class="invalid-feedback">{{ textError('layout') }}</div>
                        <div class="form-text">
                            @if ($canCustomize)
                                {{ __('home_builder::home_builder.customize_hint') }}
                            @elseif ($block->layout === Block::LAYOUT_TEMPLATE && $block->templateView() && Route::has('admin.files.edit'))
                                <a href="{{ route('admin.files.edit', ['root' => 'custom', 'path' => 'views/' . Block::TEMPLATE_DIR, 'file' => $block->template . '.blade.php']) }}" target="_blank">{{ __('home_builder::home_builder.template_edit') }}</a>
                            @elseif (isAdmin(User::BOSS))
                                {{ __('home_builder::home_builder.customize_after_save') }}
                            @endif
                        </div>
                    </div>

                    @if ($fields)
                        @include('home_builder::admin/_fields', ['entry' => $fields])
                    @endif

                    <div class="row">
                        <div class="col-md-6 mb-3{{ hasError('items') }}">
                            <label for="items" class="form-label">{{ __('home_builder::home_builder.items') }}:</label>
                            <input type="number" class="form-control" id="items" name="items" min="1" max="50" value="{{ old('items', $block->items) }}" required>
                            <div class="invalid-feedback">{{ textError('items') }}</div>
                        </div>

                        <div class="col-md-6 mb-3{{ hasError('cache_minutes') }}">
                            <label for="cache_minutes" class="form-label">{{ __('home_builder::home_builder.cache_minutes') }}:</label>
                            <input type="number" class="form-control" id="cache_minutes" name="cache_minutes" min="0" max="1440" value="{{ old('cache_minutes', $block->cache_minutes) }}" required>
                            <div class="invalid-feedback">{{ textError('cache_minutes') }}</div>
                            <div class="form-text">{{ __('home_builder::home_builder.cache_hint') }}</div>
                        </div>
                    </div>
                    @break
            @endswitch

            <h5 class="mt-4">{{ __('home_builder::home_builder.display') }}</h5>

            @php($inputWidth = (int) old('width', $block->width))

            <div class="mb-3{{ hasError('width') }}">
                <label for="width" class="form-label">{{ __('home_builder::home_builder.width') }}:</label>
                <select class="form-select" id="width" name="width">
                    @foreach (Block::WIDTHS as $width)
                        <option value="{{ $width }}"{{ $inputWidth === $width ? ' selected' : '' }}>{{ __('home_builder::home_builder.width_' . $width) }}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback">{{ textError('width') }}</div>
                <div class="form-text">{{ __('home_builder::home_builder.width_hint') }}</div>
            </div>

            @php($inputVisibility = old('visibility', $block->visibility))

            <div class="mb-3{{ hasError('visibility') }}">
                <label for="visibility" class="form-label">{{ __('home_builder::home_builder.visibility') }}:</label>
                <select class="form-select" id="visibility" name="visibility">
                    @foreach ([Block::VISIBLE_ALL, Block::VISIBLE_GUESTS, Block::VISIBLE_USERS] as $visibility)
                        <option value="{{ $visibility }}"{{ $inputVisibility === $visibility ? ' selected' : '' }}>{{ __('home_builder::home_builder.visibility_' . $visibility) }}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback">{{ textError('visibility') }}</div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3{{ hasError('starts_at') }}">
                    <label for="starts_at" class="form-label">{{ __('home_builder::home_builder.starts_at') }}:</label>
                    <input type="datetime-local" class="form-control" id="starts_at" name="starts_at" value="{{ old('starts_at', $block->starts_at ? dateFixed($block->starts_at, 'Y-m-d\TH:i', true) : '') }}">
                    <div class="invalid-feedback">{{ textError('starts_at') }}</div>
                </div>

                <div class="col-md-6 mb-3{{ hasError('ends_at') }}">
                    <label for="ends_at" class="form-label">{{ __('home_builder::home_builder.ends_at') }}:</label>
                    <input type="datetime-local" class="form-control" id="ends_at" name="ends_at" value="{{ old('ends_at', $block->ends_at ? dateFixed($block->ends_at, 'Y-m-d\TH:i', true) : '') }}">
                    <div class="invalid-feedback">{{ textError('ends_at') }}</div>
                </div>
            </div>
            <div class="form-text mt-n2 mb-3">{{ __('home_builder::home_builder.dates_hint') }}</div>

            <div class="form-check mb-3">
                <input type="hidden" value="0" name="active">
                <input type="checkbox" class="form-check-input" value="1" name="active" id="active"{{ old('active', $block->active) ? ' checked' : '' }}>
                <label for="active" class="form-check-label">{{ __('home_builder::home_builder.active') }}</label>
            </div>

            <button class="btn btn-primary">{{ __('main.save') }}</button>
        </form>

        @if ($block->exists && $block->type === Block::ENTRIES)
            <form id="customize-form" action="{{ route('admin.home-builder.customize', ['id' => $block->id]) }}" method="post">
                @csrf
            </form>
        @endif

    </div>
@stop
