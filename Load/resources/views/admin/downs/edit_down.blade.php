@extends('layout')

@section('title', __('load::loads.edit_down') . ' ' . $down->title)

@section('header')
    <div class="float-end">
        <a class="btn btn-adaptive" href="{{ route('downs.view', ['id' => $down->id]) }}"><i class="fas fa-eye"></i></a>
    </div>

    <h1>{{ __('load::loads.edit_down') . ' ' . $down->title }}</h1>
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.loads.index') }}">{{ __('load::loads.loads') }}</a></li>

            @foreach ($down->category->getParents() as $parent)
                <li class="breadcrumb-item"><a href="{{ route('admin.loads.load', ['id' => $parent->id]) }}">{{ $parent->name }}</a></li>
            @endforeach

            <li class="breadcrumb-item active">{{ __('load::loads.edit_down') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @if (! $down->active)
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> {{ __('load::loads.pending_down1') }}
        </div>
    @endif

    @if (isAdmin('boss'))
        <div class="d-flex flex-wrap gap-2 mb-3">
            @if ($down->active)
                <form action="{{ route('admin.downs.publish', ['id' => $down->id]) }}" method="post" data-confirm="{{ __('load::loads.confirm_unpublish_down') }}">
                    @csrf
                    <button class="btn btn-adaptive btn-sm">{{ __('main.unpublish') }}</button>
                </form>
            @else
                <form action="{{ route('admin.downs.publish', ['id' => $down->id]) }}" method="post" data-confirm="{{ __('load::loads.confirm_publish_down') }}">
                    @csrf
                    <button class="btn btn-success btn-sm">{{ __('main.publish') }}</button>
                </form>
            @endif

            <form action="{{ route('admin.downs.delete', ['id' => $down->id]) }}" method="post" data-confirm="{{ __('load::loads.confirm_delete_down') }}">
                @csrf
                @method('DELETE')
                <button class="btn btn-outline-danger btn-sm">{{ __('main.delete') }}</button>
            </form>
        </div>
        <hr>
    @endif

    <div class="section-form mb-3 shadow">
        <div class="mb-3">
            {{ __('main.author') }}: {{ $down->user->getProfile() }} <small class="section-date text-muted fst-italic">{{ dateFixed($down->created_at) }}</small>
        </div>

        @include('load::downs/_form')
    </div>
@stop
