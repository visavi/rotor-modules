@extends('layout')

@section('title', sprintf('%s - %s (%s)', __('forum::forums.forums'), __('forum::forums.title_new_posts'), __('main.page_num', ['page' => $posts->currentPage()])))

@section('header')
    <h1>{{ __('forum::forums.title_new_posts') }}</h1>
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('forums.index') }}">{{ __('forum::forums.forums') }}</a></li>
            <li class="breadcrumb-item active">{{ __('forum::forums.title_new_posts') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="sort-links border-bottom pb-3 mb-3">
        {{ __('main.sort') }}:
        @foreach ($sorting as $key => $option)
            <a href="{{ route('posts.index', ['sort' => $key, 'order' => $option['inverse'] ?? 'desc', 'period' => $period]) }}" class="badge bg-{{ $option['badge'] ?? 'adaptive' }}">
                {{ $option['label'] }}{{ $option['icon'] ?? '' }}
            </a>
        @endforeach
    </div>

    {{ __('main.period') }}:
    <?php $active = (empty($period)) ? 'success' : 'adaptive'; ?>
    <a href="{{ route('posts.index', ['sort' => $sort, 'order' => $order]) }}" class="badge bg-{{ $active }}">{{ __('main.all_time') }}</a>

    <?php $active = ($period === 365) ? 'success' : 'adaptive'; ?>
    <a href="{{ route('posts.index', ['period' => 365, 'sort' => $sort, 'order' => $order]) }}" class="badge bg-{{ $active }}">{{ __('main.last_year') }}</a>

    <?php $active = ($period === 30) ? 'success' : 'adaptive'; ?>
    <a href="{{ route('posts.index', ['period' => 30, 'sort' => $sort, 'order' => $order]) }}" class="badge bg-{{ $active }}">{{ __('main.last_month') }}</a>

    <?php $active = ($period === 7) ? 'success' : 'adaptive'; ?>
    <a href="{{ route('posts.index', ['period' => 7, 'sort' => $sort, 'order' => $order]) }}" class="badge bg-{{ $active }}">{{ __('main.last_week') }}</a>

    <?php $active = ($period === 1) ? 'success' : 'adaptive'; ?>
    <a href="{{ route('posts.index', ['period' => 1, 'sort' => $sort, 'order' => $order]) }}" class="badge bg-{{ $active }}">{{ __('main.last_day') }}</a>
    <hr>

    @if ($posts->isNotEmpty())
        @foreach ($posts as $data)
            <div class="section mb-3 shadow">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <i class="fa fa-file-alt"></i> <a href="{{ route('topics.topic', ['id' => $data->topic_id, 'pid' => $data->id]) }}" class="section-title">{{ $data->topic->title }}</a>
                    </div>
                    <div class="ms-2 flex-shrink-0">
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
