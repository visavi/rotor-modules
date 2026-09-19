@extends('layout')

@section('title', __('blog::blogs.blogs') . ' - ' . __('blog::blogs.blogs_list'))

@section('header')
    @if (getUser())
        <div class="float-end">
            <a class="btn btn-success" href="{{ route('blogs.create') }}">{{ __('blog::blogs.add') }}</a>

            @if (isAdmin())
                <a class="btn btn-adaptive" href="{{ route('admin.blogs.index') }}"><i class="fas fa-wrench"></i></a>
            @endif
        </div>
    @endif

    <h1>{{ __('blog::blogs.blogs') }}</h1>
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active">{{ __('blog::blogs.blogs') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="border-bottom pb-3 mb-3">
        @if (getUser())
            {{ __('main.my') }}:
            <a href="{{ route('articles.user-articles') }}" class="badge bg-adaptive">{{ __('blog::blogs.articles') }}</a>
            <a href="{{ route('articles.user-comments') }}" class="badge bg-adaptive">{{ __('main.comments') }}</a>
        @endif

        {{ __('main.new') }}:
        <a href="{{ route('articles.index') }}" class="badge bg-adaptive">{{ __('blog::blogs.articles') }}</a>
        <a href="{{ route('articles.new-comments') }}" class="badge bg-adaptive">{{ __('main.comments') }}</a>

        <div class="mt-2">
            <i class="fa fa-rss"></i> <a class="me-3" href="{{ route('blogs.rss') }}">{{ __('main.rss') }}</a><i class="fa fa-tags"></i> <a class="me-3" href="{{ route('blogs.tags') }}">{{ __('blog::blogs.tag_cloud') }}</a><i class="fa fa-users"></i> <a class="me-3" href="{{ route('blogs.authors') }}">{{ __('blog::blogs.authors') }}</a>
        </div>
    </div>

    @foreach ($categories as $key => $category)
        <div class="section mb-3 shadow">
            <div class="section-header d-flex align-items-start position-relative">
                <div class="flex-grow-1">
                    <i class="fa fa-folder-open text-muted"></i>
                    <a href="{{ route('blogs.blog', ['id' => $category->id]) }}" class="section-title position-relative">{{ $category->name }}</a>

                    <span class="badge bg-adaptive" data-bs-toggle="tooltip" title="{{ __('blog::blogs.articles') }} / {{ __('main.new') }}">
                        @if ($category->new)
                            {{ $category->total_articles }}/<span class="text-danger">+{{ $category->new->count_articles }}</span>
                        @else
                            {{ $category->total_articles }}
                        @endif
                    </span>
                </div>

                @if ($category->children->isNotEmpty())
                    <div>
                        <a data-bs-toggle="collapse" class="stretched-link" href="#section_{{ $category->id }}">
                            <i class="treeview-indicator fas fa-angle-down"></i>
                        </a>
                    </div>
                @endif
            </div>

            @if ($category->children->isNotEmpty())
                <div class="collapse" id="section_{{ $category->id }}">
                    <div class="section-content border-top p-2">
                        @foreach ($category->children as $child)
                            <div>
                                <i class="fas fa-angle-right"></i>
                                <a href="{{ route('blogs.blog', ['id' => $child->id]) }}">{{ $child->name }}</a>

                                <span class="badge bg-adaptive" data-bs-toggle="tooltip" title="{{ __('blog::blogs.articles') }} / {{ __('main.new') }}">
                                    @if ($child->new)
                                        {{ $child->total_articles }}/<span class="text-danger">+{{ $child->new->count_articles }}</span>
                                    @else
                                        {{ $child->total_articles }}
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="section-body border-top">
                @if ($category->lastArticle)
                    <a href="{{ route('articles.view', ['slug' => $category->lastArticle->slug]) }}">{{ $category->lastArticle->title }}</a>

                    @if ($category->lastArticle->isNew())
                        <span class="badge text-bg-success">NEW</span>
                    @endif
                    <small class="text-muted">
                        — {{ $category->lastArticle->user->getProfile() }}
                        <span class="section-date fst-italic">{{ dateFixed($category->lastArticle->created_at) }}</span>
                    </small>
                @else
                    {{ __('blog::blogs.empty_articles') }}
                @endif
            </div>
        </div>
    @endforeach

@stop
