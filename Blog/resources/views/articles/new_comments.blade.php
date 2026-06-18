@extends('layout')

@section('title', sprintf('%s - %s (%s)', __('blog::blogs.blogs'), __('blog::blogs.new_comments'), __('main.page_num', ['page' => $comments->currentPage()])))

@section('header')
    <h1>{{ __('blog::blogs.new_comments') }}</h1>
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('blogs.index') }}">{{ __('blog::blogs.blogs') }}</a></li>
            <li class="breadcrumb-item active">{{ __('blog::blogs.new_comments') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @if ($comments->isNotEmpty())
        @foreach ($comments as $comment)
            <div class="section mb-3 shadow">
                <div class="d-flex align-items-center mb-2">
                    <div class="flex-grow-1">
                        <i class="fa fa-comment"></i>
                        <a href="{{ route('articles.view', ['slug' => $comment->relate->slug]) }}#comment_{{ $comment->id }}" class="section-title">{{ $comment->title }}</a> <span class="badge bg-adaptive">{{ $comment->count_comments }}</span>
                    </div>

                    <div class="ms-2 flex-shrink-0 d-flex align-items-center">
                        @if (isAdmin())
                            <span class="js-actions me-1">
                                <a href="#" onclick="return deleteComment(this)" data-rid="{{ $comment->relate_id }}" data-id="{{ $comment->id }}" data-type="{{ $comment->relate->getMorphClass() }}" data-bs-toggle="tooltip" title="Удалить"><i class="fa fa-times text-muted"></i></a>
                            </span>
                        @endif
                        @include('app/_rating', ['model' => $comment, 'vote' => $comment->poll?->vote])
                    </div>
                </div>

                <div class="section-content">
                    <div class="section-message">
                        {{ $comment->getText() }}
                    </div>

                    {{ __('main.posted') }}: {{ $comment->user->getProfile() }}
                    <small class="section-date text-muted fst-italic">{{ dateFixed($comment->created_at) }}</small><br>
                    @if (isAdmin())
                        <div class="small text-muted fst-italic mt-2">
                            {{ $comment->brow }}, {{ $comment->ip }}
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @else
        {{ showError(__('main.empty_comments')) }}
    @endif

    {{ $comments->links() }}
@stop
