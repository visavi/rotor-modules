@extends('layout')

@section('title', __('load::loads.loads'))

@section('header')
    <div class="float-end">
        <a class="btn btn-adaptive" href="{{ route('loads.index') }}"><i class="fas fa-eye"></i></a>
    </div>

    <h1>{{ __('load::loads.loads') }}</h1>
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item active">{{ __('load::loads.loads') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @if ($new)
        <a href="{{ route('admin.downs.new') }}" class="btn btn-success btn-sm">{{ __('load::loads.pending') }} <span class="badge bg-adaptive">{{ $new }}</span></a>
        <hr>
    @endif

    @if ($categories->isNotEmpty())
        <div class="section mb-3 shadow">
            <div class="section-body">
                <x-category-tree :items="$categories"
                                 :action="route('admin.loads.sort')"
                                 row="load::admin/downs/_row"
                                 :sortable="isAdmin('boss')" />
            </div>
        </div>

        @if (isAdmin('boss'))
            @each('load::admin/downs/_delete', $categories, 'load')
        @endif
    @else
        {{ showError(__('load::loads.empty_loads')) }}
    @endif

    @if (isAdmin('boss'))
        <div class="section-form mb-3 shadow">
            <form action="{{ route('admin.loads.create') }}" method="post">
                @csrf
                <div class="input-group{{ hasError('name') }}">
                    <input type="text" class="form-control" id="name" name="name" maxlength="{{ setting('down_category_max') }}" value="{{ old('name') }}" placeholder="{{ __('load::loads.load') }}" required>
                    <button class="btn btn-primary">{{ __('load::loads.create_load') }}</button>
                </div>
                <div class="invalid-feedback">{{ textError('name') }}</div>
            </form>
        </div>

        <form action="{{ route('admin.loads.restatement') }}" method="post">
            @csrf
            <button class="btn btn-primary">
                <i class="fa fa-sync"></i> {{ __('main.recount') }}
            </button>
        </form>
    @endif
@stop
