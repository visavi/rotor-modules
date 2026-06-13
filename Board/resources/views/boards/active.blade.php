@extends('layout')

@section('title', __('board::boards.my_items'))

@section('header')
    <div class="float-end">
        @if (isAdmin() || (getUser() && setting('boards_create')))
            <a class="btn btn-success" href="{{ route('items.create') }}">{{ __('main.add') }}</a>
        @endif
    </div>

    <h1>{{ __('board::boards.my_items') }}</h1>
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('boards.index') }}">{{ __('board::boards.boards') }}</a></li>
            <li class="breadcrumb-item active">{{ __('board::boards.my_items') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="mb-3">
        @if ($type === 'active')
            <a class="btn btn-primary btn-sm" href="{{ route('boards.active', ['type' => 'active', 'sort' => $sort, 'order' => $order]) }}">{{ __('board::boards.active') }} <span class="badge bg-adaptive">{{ $items->total() }}</span></a>
            <a class="btn btn-adaptive btn-sm" href="{{ route('boards.active', ['type' => 'archive', 'sort' => $sort, 'order' => $order]) }}">{{ __('board::boards.archive') }} <span class="badge bg-adaptive">{{ $otherCount }}</span></a>
        @else
            <a class="btn btn-adaptive btn-sm" href="{{ route('boards.active', ['type' => 'active', 'sort' => $sort, 'order' => $order]) }}">{{ __('board::boards.active') }} <span class="badge bg-adaptive">{{ $otherCount }}</span></a>
            <a class="btn btn-primary btn-sm" href="{{ route('boards.active', ['type' => 'archive', 'sort' => $sort, 'order' => $order]) }}">{{ __('board::boards.archive') }} <span class="badge bg-adaptive">{{ $items->total() }}</span></a>
        @endif
    </div>

    <div class="sort-links border-bottom pb-3 mb-3">
        {{ __('main.sort') }}:
        @foreach ($sorting as $key => $option)
            <a href="{{ route('boards.active', ['type' => $type, 'sort' => $key, 'order' => $option['inverse'] ?? 'desc']) }}" class="badge bg-{{ $option['badge'] ?? 'adaptive' }}">
                {{ $option['label'] }}{{ $option['icon'] ?? '' }}
            </a>
        @endforeach
    </div>

    @if ($items->isNotEmpty())
        @foreach ($items as $item)
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <a href="{{ route('items.view', ['id' => $item->id]) }}">{{ $item->getFirstImage() }}</a>
                                </div>
                                <div class="col-md-7">
                                    <div class="float-end" data-bs-toggle="tooltip" title="{{ __('main.views') }}">
                                        <i class="far fa-eye"></i> {{ $item->visits }}
                                    </div>

                                    <h5><a href="{{ route('items.view', ['id' => $item->id]) }}">{{ $item->title }}</a></h5>
                                    <small><i class="fas fa-angle-right"></i> <a href="{{ route('boards.index', ['id' => $item->category->id]) }}">{{ $item->category->name }}</a></small>
                                    <div class="section-content short-view">
                                        <div class="section-message">
                                            {{ $item->getText() }}
                                        </div>
                                    </div>
                                    <div>
                                        <i class="fa fa-user-circle"></i> {{ $item->user->getProfile() }}
                                        <small class="section-date text-muted fst-italic">{{ dateFixed($item->created_at) }}</small>
                                        <br>

                                        @if ($item->expires_at > SITETIME)
                                            <i class="fas fa-clock"></i> {{ __('board::boards.expires_in') }} {{ formatTime($item->expires_at - SITETIME) }}
                                        @else
                                            <span class="badge bg-danger">{{ __('board::boards.item_not_active') }}</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    @if ($item->price)
                                        <div class="text-md-end fs-5 fw-bold text-info text-nowrap">{{ $item->price }} {{ setting('currency') }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        {{ showError(__('board::boards.empty_items')) }}
    @endif

    {{ $items->links() }}
@stop
