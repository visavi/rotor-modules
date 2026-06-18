@extends('layout')

@section('title', __('offer::offers.section'))

@section('header')
    <div class="float-end">
        @if (getUser())
            <a class="btn btn-success" href="{{ route('offers.create', ['type' => $type]) }}">{{ __('main.add') }}</a>

            @if (isAdmin())
                <a class="btn btn-adaptive" href="{{ route('admin.offers.index', ['type' => $type, 'page' => $offers->currentPage()]) }}"><i class="fas fa-wrench"></i></a>
            @endif
        @endif
    </div>

    <h1>{{ __('offer::offers.section') }}</h1>
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item active">{{ __('offer::offers.section') }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="mb-3">
        <?php $active = ($type === 'offer') ? 'primary' : 'adaptive'; ?>
        <a class="btn btn-{{ $active }} btn-sm" href="{{ route('offers.index', ['type' => 'offer', 'sort' => $sort, 'order' => $order]) }}">{{ __('offer::offers.offers') }} <span class="badge bg-adaptive">{{ $offerCount }}</span></a>

        <?php $active = ($type === 'issue') ? 'primary' : 'adaptive'; ?>
        <a class="btn btn-{{ $active }} btn-sm" href="{{ route('offers.index', ['type' => 'issue', 'sort' => $sort, 'order' => $order]) }}">{{ __('offer::offers.problems') }} <span class="badge bg-adaptive">{{ $issueCount }}</span></a>
    </div>

    @if ($offers->isNotEmpty())
        <div class="sort-links border-bottom pb-3 mb-3">
            {{ __('main.sort') }}:
            @foreach ($sorting as $key => $option)
                <a href="{{ route('offers.index', ['type' => $type, 'sort' => $key, 'order' => $option['inverse'] ?? 'desc']) }}" class="badge bg-{{ $option['badge'] ?? 'adaptive' }}">
                    {{ $option['label'] }}{{ $option['icon'] ?? '' }}
                </a>
            @endforeach
        </div>

        @foreach ($offers as $data)
            <div class="section mb-3 shadow">
                <div class="float-end">
                    @include('app/_rating', ['model' => $data, 'vote' => $data->poll?->vote])
                </div>

                <div class="section-title">
                    <i class="fa fa-file"></i>
                    <a href="{{ route('offers.view', ['id' => $data->id]) }}">{{ $data->title }}</a>
                </div>

                <div class="section-body">
                    {{ $data->getStatus() }}<br>
                    {{ $data->getText() }}<br>
                    {{ __('main.added') }}: {{ $data->user->getProfile() }}
                    <small class="section-date text-muted fst-italic">{{ dateFixed($data->created_at) }}</small><br>
                    <a href="{{ route('offers.view', ['id' => $data->id]) }}#comments">{{ __('main.comments') }}</a> <span class="badge bg-adaptive">{{ $data->count_comments }}</span>
                </div>
            </div>
        @endforeach

        {{ $offers->links() }}

        <div class="mb-3">
            {{ __('main.total') }}: <b>{{ $offers->total() }}</b>
        </div>
    @else
        {{ showError(__('main.empty_records')) }}
    @endif
@stop
