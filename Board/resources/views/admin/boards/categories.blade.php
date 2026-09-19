@extends('layout')

@section('title', __('board::boards.categories'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.boards.index') }}">{{ __('board::boards.boards') }}</a></li>
            <li class="breadcrumb-item active">{{ __('board::boards.categories') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @if ($boards->isNotEmpty())
        <div class="section mb-3 shadow">
            <div class="section-body">
                <x-category-tree :items="$boards"
                                 :action="route('admin.boards.sort')"
                                 row="board::admin/boards/_row" />
            </div>
        </div>

        @each('board::admin/boards/_delete', $boards, 'board')
    @endif

    <div class="section-form mb-3 shadow">
        <form action="{{ route('admin.boards.create') }}" method="post">
            @csrf
            <div class="input-group{{ hasError('name') }}">
                <input type="text" class="form-control" id="name" name="name" maxlength="{{ setting('board_category_max') }}" value="{{ old('name') }}" placeholder="{{ __('board::boards.category') }}" required>
                <button class="btn btn-primary">{{ __('main.create') }}</button>
            </div>
            <div class="invalid-feedback">{{ textError('name') }}</div>
        </form>
    </div>
@stop
