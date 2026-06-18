@extends('layout')

@section('title', sprintf('%s - %s (%s)', __('forum::forums.forums'), __('forum::forums.title_active_posts', ['user' => $user->getName()]), __('main.page_num', ['page' => $posts->currentPage()])))

@section('header')
    <h1>{{ __('forum::forums.title_active_posts', ['user' => $user->getName()]) }}</h1>
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('forums.index') }}">{{ __('forum::forums.forums') }}</a></li>
            <li class="breadcrumb-item active">{{ __('forum::forums.title_active_posts', ['user' => $user->getName()]) }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @if ($posts->isNotEmpty())
        <div class="sort-links border-bottom pb-3 mb-3">
            {{ __('main.sort') }}:
            @foreach ($sorting as $key => $option)
                <a href="{{ route('forums.active-posts', ['sort' => $key, 'order' => $option['inverse'] ?? 'desc', 'user' => $user->login]) }}" class="badge bg-{{ $option['badge'] ?? 'adaptive' }}">
                    {{ $option['label'] }}{{ $option['icon'] ?? '' }}
                </a>
            @endforeach
        </div>

        @foreach ($posts as $data)
            <div class="section mb-3 shadow">
                <div class="d-flex align-items-center mb-2">
                    <div class="flex-grow-1">
                        <i class="fa fa-file-alt"></i> <a href="{{ route('topics.topic', ['id' => $data->topic_id, 'pid' => $data->id]) }}" class="section-title">{{ $data->topic->title }}</a>
                    </div>
                    <div class="ms-2 flex-shrink-0 d-flex align-items-center">
                        @if (isAdmin())
                            <span class="js-actions me-1">
                                <a href="{{ route('forums.active-delete', ['id' => $data->id]) }}" onclick="return deletePost(this)" data-bs-toggle="tooltip" title="{{ __('main.delete') }}"><i class="fa fa-times text-muted"></i></a>
                            </span>
                        @endif
                        @include('app/_rating', ['model' => $data, 'vote' => $data->poll?->vote])
                    </div>
                </div>

                <div class="section-message">
                    {{ $data->getText() }}
                </div>

                {{ __('main.posted') }}: {{ $data->user->getName() }}
                <small class="section-date text-muted fst-italic">{{ dateFixed($data->created_at) }}</small>
                @if (isAdmin())
                    <div class="small text-muted fst-italic mt-2">({{ $data->brow }}, {{ $data->ip }})</div>
                @endif
            </div>
        @endforeach
    @else
        {{ showError(__('forum::forums.posts_not_created')) }}
    @endif

    {{ $posts->links() }}
@stop
