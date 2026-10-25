@use('App\Models\User')
@use('Modules\HomeBuilder\Models\Block')
@use('Modules\HomeBuilder\Support\Entry')
@use('Modules\HomeBuilder\Support\Presets')
@extends('layout')

@section('title', __('home_builder::home_builder.title'))

@section('header')
    <h1>{{ __('home_builder::home_builder.title') }}</h1>
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item active">{{ __('home_builder::home_builder.title') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @if (setting('homepage') !== 'home-builder')
        <div class="alert alert-info d-flex flex-wrap align-items-center gap-2">
            <div class="flex-grow-1">{{ __('home_builder::home_builder.not_selected') }}</div>

            @if (isAdmin(User::BOSS))
                <form action="{{ route('admin.home-builder.activate') }}" method="post">
                    @csrf
                    <button class="btn btn-sm btn-primary">{{ __('home_builder::home_builder.activate') }}</button>
                </form>
            @endif
        </div>
    @endif

    @php($sources = Block::sourceList())

    {{-- Пустая главная: начать с готового набора блоков, его потом можно править --}}
    @if ($blocks->isEmpty())
        <div class="section mb-3 shadow">
            <div class="section-title">{{ __('home_builder::home_builder.presets') }}</div>
            <div class="section-body mb-3">{{ __('home_builder::home_builder.presets_hint') }}</div>

            <div class="row g-2">
                @foreach (Presets::NAMES as $preset)
                    <div class="col-md-4">
                        <form action="{{ route('admin.home-builder.preset') }}" method="post" class="h-100">
                            @csrf
                            <input type="hidden" name="preset" value="{{ $preset }}">
                            <button class="btn btn-outline-primary w-100 h-100 text-start">
                                <span class="fw-bold">{{ __('home_builder::home_builder.preset_' . $preset) }}</span><br>
                                <small>{{ __('home_builder::home_builder.preset_' . $preset . '_hint') }}</small>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Порядок сохраняется сразу после переноса: ядро шлёт новый список id на sort --}}
    <div data-sortable data-sortable-url="{{ route('admin.home-builder.sort') }}">
        @foreach ($blocks as $block)
            @php($typeLabel = $block->getTypeLabel())

            <div class="section mb-3 shadow" data-key="{{ $block->id }}">
                <div class="section-header d-flex align-items-start">
                    <div class="flex-grow-1">
                        {{-- Ручка в одной строке с заголовком: отдельным flex-элементом она вставала выше крупного шрифта --}}
                        <span class="sortable-handle text-muted me-1" data-sortable-handle data-bs-toggle="tooltip" title="{{ __('home_builder::home_builder.drag') }}"><i class="fas fa-grip-vertical"></i></span>
                        <span class="section-title">{{ $block->title !== '' ? $block->title : $typeLabel }}</span>
                        @if ($block->title !== '')
                            <small class="text-muted">{{ $typeLabel }}</small>
                        @endif

                        @if ($block->type === Block::ENTRIES)
                            <div class="small text-muted">
                                {{ __('home_builder::home_builder.layout_' . $block->layout) }}:
                                @foreach ($block->sources ?? [] as $source)
                                    @if (isset($sources[$source]))
                                        {{ $sources[$source] }}{{ $loop->last ? '' : ',' }}
                                    @else
                                        <span class="text-decoration-line-through" data-bs-toggle="tooltip" title="{{ __('home_builder::home_builder.source_disabled') }}">{{ Entry::sectionLabel($source) }}</span>{{ $loop->last ? '' : ',' }}
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        <div class="small">
                            <span class="badge bg-adaptive">{{ __('home_builder::home_builder.width_' . $block->width) }}</span>
                            <span class="badge bg-adaptive">{{ __('home_builder::home_builder.visibility_' . $block->visibility) }}</span>

                            @if ($block->starts_at || $block->ends_at)
                                <span class="badge bg-adaptive">
                                    <i class="far fa-clock"></i>
                                    {{ $block->starts_at ? dateFixed($block->starts_at) : '…' }} — {{ $block->ends_at ? dateFixed($block->ends_at) : '…' }}
                                </span>
                            @endif

                            @if ($block->usesTemplate() && ! $block->templateView())
                                <span class="text-danger">{{ __('home_builder::home_builder.template_missing') }}</span>
                            @elseif (! $block->active)
                                <span class="text-muted">{{ __('home_builder::home_builder.hidden') }}</span>
                            @elseif (! $block->isShownNow())
                                <span class="text-muted">{{ __('home_builder::home_builder.out_of_dates') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="text-end text-nowrap">
                        @if ($block->isManageable())
                            <a href="{{ route('admin.home-builder.edit', ['id' => $block->id]) }}" data-bs-toggle="tooltip" title="{{ __('main.edit') }}"><i class="fas fa-pencil-alt text-muted"></i></a>

                            <form action="{{ route('admin.home-builder.delete', ['id' => $block->id]) }}" method="post" class="d-inline" data-confirm="{{ __('home_builder::home_builder.confirm_delete') }}">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-link p-0" data-bs-toggle="tooltip" title="{{ __('main.delete') }}"><i class="fas fa-times text-muted"></i></button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="section-form mb-3 shadow">
        <form action="{{ route('admin.home-builder.create') }}" method="get">
            <label for="type" class="form-label">{{ __('home_builder::home_builder.add_block') }}:</label>
            <div class="input-group">
                <select class="form-select" id="type" name="type">
                    @foreach ($types as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-primary">{{ __('main.add') }}</button>
            </div>
        </form>
    </div>
@stop
