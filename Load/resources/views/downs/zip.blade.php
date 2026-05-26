@extends('layout')

@section('title', __('load::loads.view_archive') . ' ' . $down->title)

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('loads.index') }}">{{ __('load::loads.loads') }}</a></li>

            @foreach ($down->category->getParents() as $parent)
                <li class="breadcrumb-item"><a href="{{ route('loads.load', ['id' => $parent->id]) }}">{{ $parent->name }}</a></li>
            @endforeach

            <li class="breadcrumb-item"><a href="{{ route('downs.view', ['id' => $down->id]) }}">{{ $down->title }}</a></li>
            <li class="breadcrumb-item active">{{ __('load::loads.view_archive') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @if (! empty($tree['__files']) || ! empty($tree['__dirs']))
        <small class="text-muted d-block mb-2">{{ __('main.total') }}: {{ $totalCount }} / {{ formatSize($totalSize) }}</small>

        <div class="mb-3">
            @include('load::downs/_zip_tree', ['tree' => $tree])
        </div>
    @else
        {{ showError(__('load::loads.empty_archive')) }}
    @endif
@stop
