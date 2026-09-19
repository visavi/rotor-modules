@extends('layout')

@section('title', __('blog::blogs.blogs'))

@section('header')
    <div class="float-end">
        <a class="btn btn-adaptive" href="{{ route('blogs.index') }}"><i class="fas fa-eye"></i></a>
    </div>

    <h1>{{ __('blog::blogs.blogs') }}</h1>
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item active">{{ __('blog::blogs.blogs') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @if ($new)
        <a href="{{ route('admin.articles.new') }}" class="btn btn-success btn-sm">{{ __('blog::blogs.pending_articles') }} <span class="badge bg-adaptive">{{ $new }}</span></a>
        <hr>
    @endif

    @if ($categories->isNotEmpty())
        <div class="section mb-3 shadow">
            <div class="section-body">
                <x-category-tree :items="$categories"
                                 :action="route('admin.blogs.sort')"
                                 row="blog::admin/articles/_row"
                                 :sortable="isAdmin('boss')" />
            </div>
        </div>

        @if (isAdmin('boss'))
            @each('blog::admin/articles/_delete', $categories, 'blog')
        @endif
    @else
        {{ showError(__('blog::blogs.empty_blogs')) }}
    @endif

    @if (isAdmin('boss'))
        <div class="section-form my-3 shadow">
            <form action="{{ route('admin.blogs.create') }}" method="post">
                @csrf
                <div class="input-group{{ hasError('name') }}">
                    <input type="text" class="form-control" id="name" name="name" maxlength="{{ setting('blog_category_max') }}" value="{{ old('name') }}" placeholder="{{ __('blog::blogs.blog') }}" required>
                    <button class="btn btn-primary">{{ __('main.create') }}</button>
                </div>
                <div class="invalid-feedback">{{ textError('name') }}</div>
            </form>
        </div>

        <form action="{{ route('admin.blogs.restatement') }}" method="post">
            @csrf
            <button class="btn btn-primary">
                <i class="fa fa-sync"></i> {{ __('main.recount') }}
            </button>
        </form>
    @endif
@stop
