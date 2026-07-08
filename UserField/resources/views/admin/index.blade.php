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
        @foreach ($fields as $field)
            <div class="section mb-3 shadow">
                <div class="section-title">
                    <i class="far fa-list-alt"></i>
                    {{ $field->name }}
                    <div class="float-end">
                        <a href="/admin/user-fields/{{ $field->id }}/edit"><i class="fas fa-pencil-alt text-muted"></i></a>
                        <a href="/admin/user-fields/{{ $field->id }}" onclick="return deletePost(this)"><i class="fa fa-times text-muted"></i></a>
                    </div>
                </div>

                <div class="section-content">
                    <span class="badge {{ $field->type === 'input' ? 'bg-success' : 'bg-primary' }}">{{ __('main.type') }}: {{ __('user_field::user_fields.' . $field->type) }}</span><br>
                    {{ __('main.min') }}: {{ $field->min }},
                    {{ __('main.max') }}: {{ $field->max }}<br>
                    {{ __('user_field::user_fields.required') }}: {{ $field->required ? __('main.yes') : __('main.no') }}
                </div>
            </div>
        @endforeach
    @else
        {{ showError(__('user_field::user_fields.empty_fields')) }}
    @endif
@stop
