@extends('layout')

@section('title', __('forum::forums.forums'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item active">{{ __('forum::forums.forums') }}</li>
        </ol>
    </nav>
@stop

@section('header')
    <div class="float-end">
        <a class="btn btn-adaptive" href="{{ route('forums.index') }}"><i class="fas fa-eye"></i></a>
    </div>

    <h1>{{ __('forum::forums.forums') }}</h1>
@stop

@section('content')
    @if ($forums->isNotEmpty())
        <div class="section mb-3 shadow">
            <div class="section-body">
                <x-category-tree :items="$forums"
                                 :action="route('admin.forums.sort')"
                                 row="forum::admin/forums/_row"
                                 :sortable="isAdmin('boss')" />
            </div>
        </div>

        @if (isAdmin('boss'))
            @each('forum::admin/forums/_delete', $forums, 'forum')
        @endif
    @else
        {{ showError(__('forum::forums.empty_forums')) }}
    @endif

    @if (isAdmin('boss'))
        <div class="section-form mb-3 shadow">
            <form action="{{ route('admin.forums.create') }}" method="post">
                @csrf
                <div class="input-group{{ hasError('title') }}">
                    <input type="text" class="form-control" id="title" name="title" maxlength="{{ setting('forum_category_max') }}" value="{{ old('title') }}" placeholder="{{ __('forum::forums.forum') }}" required>
                    <button class="btn btn-primary">{{ __('forum::forums.create_forum') }}</button>
                </div>
                <div class="invalid-feedback">{{ textError('title') }}</div>
            </form>
        </div>

        <form action="{{ route('admin.forums.restatement') }}" method="post">
            @csrf
            <button class="btn btn-primary">
                <i class="fa fa-sync"></i> {{ __('main.recount') }}
            </button>
        </form>
    @endif
@stop
