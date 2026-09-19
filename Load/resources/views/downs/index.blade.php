@extends('layout')

@section('title', __('load::loads.loads'))

@section('header')
    <div class="float-end">
        @if (isAdmin() || (getUser() && setting('downupload')))
            <a class="btn btn-success" href="{{ route('downs.create') }}">{{ __('main.add') }}</a>

            @if (isAdmin('admin'))
                <a class="btn btn-adaptive" href="{{ route('admin.loads.index') }}"><i class="fas fa-wrench"></i></a>
            @endif
        @endif
    </div>

    <h1>{{ __('load::loads.loads') }}</h1>
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active">{{ __('load::loads.loads') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @if (getUser())
        {{ __('main.my') }}:
        <a href="{{ route('downs.active-files') }}" class="badge bg-adaptive">{{ __('load::loads.downs') }}</a>
        <a href="{{ route('downs.active-comments') }}" class="badge bg-adaptive">{{ __('main.comments') }}</a>
    @endif

    {{ __('main.new') }}:
    <a href="{{ route('downs.new-files') }}" class="badge bg-adaptive">{{ __('load::loads.downs') }}</a>
    <a href="{{ route('downs.new-comments') }}" class="badge bg-adaptive">{{ __('main.comments') }}</a>

    <div class="mt-2"><i class="fa fa-rss"></i> <a class="me-3" href="{{ route('loads.rss') }}">{{ __('main.rss') }}</a></div>
    <hr>

    @foreach ($categories as $category)
        <div class="section mb-3 shadow">
            <div class="section-header d-flex align-items-start position-relative">
                <div class="flex-grow-1">
                    <i class="fa fa-folder-open text-muted"></i>
                    <a href="{{ route('loads.load', ['id' => $category->id]) }}" class="section-title position-relative">{{ $category->name }}</a>

                    @if ($category->new)
                        <span class="badge bg-adaptive" data-bs-toggle="tooltip" title="{{ __('load::loads.downs') }} / {{ __('main.new') }}">{{ $category->total_downs }}/<span class="text-danger">+{{ $category->new->count_downs }}</span></span>
                    @else
                        <span class="badge bg-adaptive" data-bs-toggle="tooltip" title="{{ __('load::loads.downs') }} / {{ __('main.new') }}">{{ $category->total_downs }}</span>
                    @endif
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
                                <a href="{{ route('loads.load', ['id' => $child->id]) }}">{{ $child->name }}</a>

                                @if ($child->new)
                                    <span class="badge bg-adaptive" data-bs-toggle="tooltip" title="{{ __('load::loads.downs') }} / {{ __('main.new') }}">{{ $child->total_downs }}/<span class="text-danger">+{{ $child->new->count_downs }}</span></span>
                                @else
                                    <span class="badge bg-adaptive" data-bs-toggle="tooltip" title="{{ __('load::loads.downs') }} / {{ __('main.new') }}">{{ $child->total_downs }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="section-body border-top">
                @if ($category->lastDown)
                    <a href="{{ route('downs.view', ['id' => $category->lastDown->id]) }}">{{ $category->lastDown->title }}</a>

                    @if ($category->lastDown->isNew())
                        <span class="badge text-bg-success">NEW</span>
                    @endif
                    <small class="text-muted">
                        — {{ $category->lastDown->user->getProfile() }}
                        <span class="section-date fst-italic">{{ dateFixed($category->lastDown->created_at) }}</span>
                    </small>
                @else
                    {{ __('load::loads.empty_downs') }}
                @endif
            </div>
        </div>
    @endforeach

@stop
