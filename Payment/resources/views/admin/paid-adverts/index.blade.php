@extends('layout')

@section('title', __('payment::payments.paid_adverts.title'))

@section('header')
    <div class="float-end">
        <a class="btn btn-success" href="/admin/paid-adverts/{{ in_array($place, \Modules\Payment\Models\PaidAdvert::CARD_PLACES, true) ? 'create-card' : 'create' }}?place={{ $place }}">{{ __('main.create') }}</a>
    </div>

    <h1>{{ __('payment::payments.paid_adverts.title') }}</h1>
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item active">{{ __('payment::payments.paid_adverts.title') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="mb-3">
        @foreach ($places as $placeName)
            <?php $active = ($place === $placeName) ? 'primary' : 'adaptive'; ?>
            <a class="btn btn-{{ $active }} btn-sm" href="/admin/paid-adverts?place={{ $placeName }}">{{ (new \Modules\Payment\Models\PaidAdvert(['place' => $placeName]))->getPlaceName() }} <span class="badge bg-adaptive">{{ $totals[$placeName] }}</span></a>
        @endforeach
    </div>

    @if ($adverts->isNotEmpty())
        @foreach ($adverts as $data)
            @if ($data->isCard())
                @include('payment::card/_card', ['card' => $data->cardData(), 'layout' => 'feed'])

                <div class="mb-3 mt-n2">
                    <i class="far fa-user"></i> {{ $data->user->getProfile() }}
                    <small class="section-date text-muted fst-italic">{{ __('payment::payments.paid_adverts.expires') }}: {{ dateFixed($data->deleted_at) }}</small>
                    @if ($data->deleted_at?->lt(now()))
                        <span class="badge bg-danger">{{ __('payment::payments.paid_adverts.expired') }}</span>
                    @endif

                    <a class="ms-2" href="/admin/paid-adverts/edit-card/{{ $data->id }}"><i class="fas fa-pencil-alt text-muted"></i></a>
                    @include('payment::admin/paid-adverts/_stop_icon')
                    <form action="/admin/paid-adverts/delete/{{ $data->id }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('payment::payments.paid_adverts.confirm_delete_advert') }}')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-link p-0"><i class="fa fa-times text-muted"></i></button>
                    </form>
                </div>
                @continue
            @endif

            <div class="section mb-3 shadow">
                <div class="section-title">
                    <i class="fas fa-globe-americas"></i>
                    <a href="{{ $data->url }}">{{ $data->names[0] }}</a>
                    @if (count($data->names) > 1)
                        <span class="badge bg-info">{{ count($data->names) }}</span>
                    @endif

                    @if ($data->deleted_at?->lt(now()))
                        <span class="badge bg-danger">{{ __('payment::payments.paid_adverts.expired') }}</span>
                    @endif
                    <div class="float-end">
                        <a href="/admin/paid-adverts/edit/{{ $data->id }}"><i class="fas fa-pencil-alt text-muted"></i></a>
                        @include('payment::admin/paid-adverts/_stop_icon')
                        <form action="/admin/paid-adverts/delete/{{ $data->id }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('payment::payments.paid_adverts.confirm_delete_advert') }}')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-link p-0"><i class="fa fa-times text-muted"></i></button>
                        </form>
                    </div>
                </div>

                <div class="section-body border-top">
                    <div class="section-message">
                        {{ renderText($data->comment ?? __('main.empty_comment')) }}
                    </div>

                    <i class="far fa-user"></i> {{ $data->user->getProfile() }}
                    <small class="section-date text-muted fst-italic">{{ __('payment::payments.paid_adverts.expires') }}: {{ dateFixed($data->deleted_at) }}</small>

                    <div class="small text-muted fst-italic mt-2">
                        {{ __('payment::payments.paid_adverts.color') }}: {!! $data->color ? '<span style="color:' . $data->color .'">'. $data->color .'</span>' : '<i class="fas fa-times text-danger"></i>' !!},
                        {{ __('payment::payments.paid_adverts.bold') }}: {!! $data->bold ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' !!}
                    </div>
                </div>
            </div>
        @endforeach
    @else
        {{ showError(__('payment::payments.paid_adverts.empty_links')) }}
    @endif
@stop
