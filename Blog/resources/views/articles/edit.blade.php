@extends('layout')

@section('title', __('blog::blogs.title_edit_article'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('blogs.index') }}">{{ __('blog::blogs.blogs') }}</a></li>

            @foreach ($article->category->getParents() as $parent)
                <li class="breadcrumb-item"><a href="{{ route('blogs.blog', ['id' => $parent->id]) }}">{{ $parent->name }}</a></li>
            @endforeach

            <li class="breadcrumb-item"><a href="{{ route('articles.view', ['slug' => $article->slug]) }}">{{ $article->title }}</a></li>
            <li class="breadcrumb-item active">{{ __('blog::blogs.title_edit_article') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @if (! $article->active)
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> {{ __('blog::blogs.article_not_active_text') }}<br>
        </div>
    @endif

    @if (! $article->isPublished())
        <div class="alert alert-info">
            <i class="fas fa-exclamation-triangle"></i> {{ __('blog::blogs.article_delayed_text') }}<br>
        </div>
    @endif

    @if ($article->draft)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> {{ __('blog::blogs.article_draft_text') }}<br>
        </div>
    @endif

    <div class="section-form mb-3 shadow">
        @include('blog::articles/_form')
    </div>


@stop
