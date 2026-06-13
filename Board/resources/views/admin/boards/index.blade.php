@extends('layout')

@section('title', __('board::boards.boards'))

@section('header')
    <div class="float-end">
        <a class="btn btn-adaptive" href="{{ route('boards.index', ['id' => $board ?? null, 'page' => $items->currentPage()]) }}"><i class="fas fa-wrench"></i></a>
    </div>

    @if ($board)
        <h1>{{ $board->name }} <small>({{ __('board::boards.boards') }}: {{ $board->count_items }})</small></h1>
    @else
        <h1>{{ __('board::boards.boards') }}</h1>
    @endif
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>

            @if ($board)
                <li class="breadcrumb-item"><a href="{{ route('admin.boards.index') }}">{{ __('board::boards.boards') }}</a></li>

                @foreach ($board->getParents() as $parent)
                    <li class="breadcrumb-item"><a href="{{ route('admin.boards.index', ['id' => $parent->id]) }}">{{ $parent->name }}</a></li>
                @endforeach
            @else
                <li class="breadcrumb-item active">{{ __('board::boards.boards') }}</li>
            @endif
        </ol>
    </nav>
@stop

@section('content')
    @if ($boards->isNotEmpty())
        <div class="row row-cols-2 row-cols-md-4 g-2 mb-3">
            @foreach ($boards as $child)
                <div class="col">
                    <a href="{{ route('admin.boards.index', ['id' => $child->id]) }}">{{ $child->name }}</a> <span class="badge bg-adaptive">{{ $child->count_items + $child->children->sum('count_items') }}</span>

                    @if (isAdmin('boss'))
                        <a href="{{ route('admin.boards.edit', ['id' => $child->id]) }}"><i class="fa fa-pencil-alt"></i></a>
                        <form action="{{ route('admin.boards.delete', ['id' => $child->id]) }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('board::boards.confirm_delete_category') }}')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-link p-0"><i class="fa fa-times"></i></button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    <div class="sort-links border-bottom pb-3 mb-3">
        {{ __('main.sort') }}:
        @foreach ($sorting as $key => $option)
            <a href="{{ route('admin.boards.index', ['id' => $board?->id, 'sort' => $key, 'order' => $option['inverse'] ?? 'desc']) }}" class="badge bg-{{ $option['badge'] ?? 'adaptive' }}">
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
                                    <div class="float-end">
                                        <span data-bs-toggle="tooltip" title="{{ __('main.views') }}">
                                            <i class="far fa-eye"></i> {{ $item->visits }}
                                        </span>

                                        <a href="{{ route('admin.items.edit', ['id' => $item->id]) }}" data-bs-toggle="tooltip" title="{{ __('main.edit') }}"><i class="fa fa-pencil-alt"></i></a>
                                        <form action="{{ route('admin.items.delete', ['id' => $item->id]) }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('board::boards.confirm_delete_item') }}')" data-bs-toggle="tooltip" title="{{ __('main.delete') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-link p-0"><i class="fa fa-times"></i></button>
                                        </form>
                                    </div>

                                    <h5><a href="{{ route('items.view', ['id' => $item->id]) }}">{{ $item->title }}</a></h5>
                                    <small><i class="fas fa-angle-right"></i> <a href="{{ route('boards.index', ['id' => $item->category->id]) }}">{{ $item->category->name }}</a></small>
                                    <div class="section-content short-view">
                                        <div class="section-message">
                                            {{ $item->getText() }}
                                        </div>
                                    </div>

                                    @if ($item->phone)
                                        <p class="card-text">
                                            <a href="tel:{{ $item->phone }}" class="text-decoration-none">
                                                <i class="fa-solid fa-phone fs-5 me-2"></i> {{ $item->phone }}
                                            </a>
                                        </p>
                                    @endif

                                    <div>
                                        <i class="fa fa-user-circle"></i> {{ $item->user->getProfile() }}
                                        <small class="section-date text-muted fst-italic">
                                            {{ dateFixed($item->created_at) }}
                                        </small>
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

    @if (isAdmin('boss'))
        <div class="mb-3">
            <i class="far fa-list-alt"></i> <a href="{{ route('admin.boards.categories') }}">{{ __('board::boards.categories') }}</a>
        </div>

        <form action="{{ route('admin.boards.restatement') }}" method="post">
            @csrf
            <button class="btn btn-primary">
                <i class="fa fa-sync"></i> {{ __('main.recount') }}
            </button>
        </form>
    @endif
@stop
