@extends('layout')

@section('title', $item->title)

@section('description', truncateDescription($item->text))
@section('canonical', $item->getViewUrl())
@section('og_type', 'article')
@section('image', $item->getOgImage())

@section('header')
    @if (getUser())
        <div class="float-end">
            @if (getUser('id') === $item->user->id)
                <a class="btn btn-success" href="{{ route('items.edit', ['id' => $item->id]) }}">{{ __('main.change') }}</a>
            @endif

            @if (isAdmin())
                <div class="btn-group">
                    <button type="button" class="btn btn-adaptive dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-wrench"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item" href="{{ route('admin.items.edit', ['id' => $item->id]) }}">{{ __('main.edit') }}</a>
                        <form action="{{ route('admin.items.delete', ['id' => $item->id]) }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('board::boards.confirm_delete_item') }}')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-link dropdown-item">{{ __('main.delete') }}</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <h1>{{ $item->title }}</h1>
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('boards.index') }}">{{ __('board::boards.boards') }}</a></li>

            @foreach ($item->category->getParents() as $parent)
                <li class="breadcrumb-item"><a href="{{ route('boards.index', ['id' => $parent->id]) }}">{{ $parent->name }}</a></li>
            @endforeach

            <li class="breadcrumb-item active">{{ $item->title }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @if ($item->expires_at->lte(now()))
        <div class="alert alert-warning">{{ __('board::boards.item_not_active') }}</div>
    @endif

    <div class="mb-3">
        @if ($item->files->isNotEmpty())
            @include('app/_media_slider', ['model' => $item])
        @endif

        <div class="section-message">
            {{ $item->getText() }}
        </div>

        @if ($item->price)
            <div class="fs-5 fw-bold text-info mt-3">{{ $item->getPrice() }}</div>
        @endif

        @php($canContact = $item->user->id && getUser('id') !== $item->user->id)
        @if ($item->phone || $canContact)
            <div class="d-flex flex-wrap align-items-center gap-3 my-3">
                @if ($canContact)
                    <a class="btn btn-primary" href="{{ route('messages.talk', ['login' => $item->user->login]) }}">
                        <i class="fa-solid fa-envelope me-1"></i> {{ __('board::boards.contact_seller') }}
                    </a>
                @endif

                @if ($item->phone)
                    <span class="fs-5">@include('board::boards/_phone', ['button' => true])</span>
                @endif
            </div>
        @endif

        <div class="text-muted small d-flex flex-wrap column-gap-3 row-gap-1 mb-3">
            @if ($item->city)
                <span><i class="fa-solid fa-location-dot"></i> <a href="{{ route('boards.index', ['city' => $item->city]) }}">{{ $item->city }}</a></span>
            @endif

            <span><i class="fa fa-user-circle"></i> {{ $item->user->getProfile() }}</span>
            <span><i class="fa-regular fa-calendar"></i> {{ dateFixed($item->updated_at) }}</span>
            <span title="{{ __('main.views') }}"><i class="far fa-eye"></i> {{ $item->visits }}</span>

            {{-- Срок нужен только тому, кто может продлить --}}
            @if ($item->expires_at->gt(now()) && (getUser('id') === $item->user_id || isAdmin()))
                <span><i class="fas fa-clock"></i> {{ __('board::boards.expires_in') }} {{ formatTime($item->expires_at->getTimestamp() - now()->timestamp) }}</span>
            @endif
        </div>

        @hook('share', ['url' => $item->getViewUrl(), 'title' => $item->title])
    </div>
@stop
