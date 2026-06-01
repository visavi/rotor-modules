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
            <div class="section-title">
                <i class="fa fa-folder-open"></i>
                <a href="{{ route('blogs.blog', ['id' => $category->id]) }}">{{ $category->name }}</a>

                <span class="badge bg-adaptive">
                    @if ($category->new)
                        {{ $category->count_articles + $category->children->sum('count_articles') }}/<span style="color:#ff0000">+{{ $category->new->count_articles }}</span>
                    @else
                        {{ $category->count_articles + $category->children->sum('count_articles') }}
                    @endif
                </span>
            </div>

            <div class="section-content">
                @if ($category->children->isNotEmpty())
                    @foreach ($category->children as $child)
                        <div>
                            <i class="fa fa-angle-right"></i>
                            <b><a href="{{ route('blogs.blog', ['id' => $child->id]) }}">{{ $child->name }}</a></b>

                            <span class="badge bg-adaptive">
                                @if ($child->new)
                                    {{ $child->count_articles }}/<span style="color:#ff0000">+{{ $child->new->count_articles }}</span>
                                @else
                                    {{ $child->count_articles }}
                                @endif
                            </span>
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="section-body border-top">
                @if ($category->lastArticle)
                    {{ __('blog::blogs.article') }}: <a href="{{ route('articles.view', ['slug' => $category->lastArticle->slug]) }}">{{ $category->lastArticle->title }}</a>

                    @if ($category->lastArticle->isNew())
                        <span class="badge text-bg-success">NEW</span>
                    @endif
                    <br>
                    {{ __('main.author') }}: {{ $category->lastArticle->user->getProfile() }}
                    <small class="section-date text-muted fst-italic">
                        {{ dateFixed($category->lastArticle->created_at) }}
                    </small>
                @else
                    {{ __('blog::blogs.empty_articles') }}
                @endif
            </div>
        </div>
    @endforeach

@stop
