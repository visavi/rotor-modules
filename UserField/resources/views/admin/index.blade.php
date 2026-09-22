@extends('layout')

@section('title', __('user_field::user_fields.title'))

@section('header')
    <div class="float-end">
        <a class="btn btn-success" href="/admin/user-fields/create">{{ __('main.create') }}</a>
    </div>

    <h1>{{ __('user_field::user_fields.title') }}</h1>
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item active">{{ __('user_field::user_fields.title') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @if ($fields->isNotEmpty())
        <div class="section mb-3 shadow">
            <div class="section-body">
                <p class="text-muted">{{ __('user_field::user_fields.sort_help') }}</p>

                <form action="{{ route('user-fields.sort') }}" method="post">
                    @csrf

                    {{-- Порядок собирает Sortable; без JS отправится порядок, отрисованный сервером --}}
                    <input type="hidden" name="order" id="user-fields-order" value="{{ $fields->pluck('id')->implode(',') }}">

                    <div data-sortable data-sortable-target="#user-fields-order">
                        @foreach ($fields as $field)
                            <div class="sortable-row" data-key="{{ $field->id }}">
                                <span class="sortable-handle text-muted" data-sortable-handle title="{{ __('user_field::user_fields.sort_drag') }}">
                                    <i class="fas fa-grip-vertical"></i>
                                </span>

                                <span class="fw-bold">{{ $field->name }}</span>

                                <span class="badge {{ $field->type === 'input' ? 'bg-success' : 'bg-primary' }}">
                                    {{ __('user_field::user_fields.' . $field->type) }}
                                </span>

                                <span class="text-muted small">
                                    {{ __('main.min') }}: {{ $field->min }},
                                    {{ __('main.max') }}: {{ $field->max }}@if ($field->required), {{ mb_strtolower(__('user_field::user_fields.required')) }}@endif
                                </span>

                                <span class="ms-auto text-nowrap">
                                    <a href="/admin/user-fields/{{ $field->id }}/edit"><i class="fas fa-pencil-alt text-muted"></i></a>
                                    <a href="/admin/user-fields/{{ $field->id }}" data-ajax data-ajax-method="delete" data-ajax-confirm data-ajax-remove=".sortable-row"><i class="fa fa-times text-muted"></i></a>
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <button class="btn btn-primary mt-3">{{ __('main.save') }}</button>
                </form>
            </div>
        </div>
    @else
        {{ showError(__('user_field::user_fields.empty_fields')) }}
    @endif
@stop
