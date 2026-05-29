@extends('layout')

@section('title', __('index.advertising'))

@section('header')
    <div class="float-end">
        <a class="btn btn-success" href="/adverts/create">{{ __('advert::adverts.create_advert') }}</a>
        <a class="btn btn-adaptive" href="/adverts"><i class="fas fa-wrench"></i></a>
    </div>

    <h1>{{ __('index.advertising') }}</h1>
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item active">{{ __('index.advertising') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    @if ($records->isNotEmpty())
        <form action="/admin/adverts/delete?page={{ $records->currentPage() }}" method="post">
            @csrf
            @foreach ($records as $data)
                <div class="section mb-3 shadow">
                    <div class="section-title">
                        <i class="fas fa-globe-americas"></i>
                        <a href="{{ $data->site }}">{{ $data->name }}</a>

                        <div class="float-end">
                            <a href="/admin/adverts/edit/{{ $data->id }}?page={{ $records->currentPage() }}"><i class="fas fa-pencil-alt text-muted"></i></a>
                            <input type="checkbox" name="del[]" value="{{ $data->id }}">
                        </div>
                    </div>

                    <div class="section-body border-top">
                        <i class="far fa-user"></i> {{ $data->user->getProfile() }}
                        <small class="section-date text-muted fst-italic">{{ __('advert::adverts.expires') }}: {{ dateFixed($data->deleted_at) }}</small>

                        <div class="small text-muted fst-italic mt-2">
                            {{ __('advert::adverts.color') }}: {!! $data->color ? '<span style="color:' . $data->color .'">'. $data->color .'</span>' : '<i class="fas fa-times text-danger"></i>' !!},
                            {{ __('advert::adverts.bold') }}: {!! $data->bold ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' !!}
                        </div>
                    </div>
                </div>
            @endforeach

            <div class="clearfix mb-3">
                <button class="btn btn-sm btn-danger float-end">{{ __('main.delete_selected') }}</button>
            </div>
        </form>

        {{ $records->links() }}

        <div class="mb-3">
            {{ __('advert::adverts.total_links') }}: <b>{{ $records->total() }}</b>
        </div>
    @else
        {{ showError(__('advert::adverts.empty_links')) }}
    @endif
@stop
