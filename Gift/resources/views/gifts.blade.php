@extends('layout')

@section('title')
    Подарки {{ $user->login }}
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/gifts">{{ trans('Gift::gifts.module') }}</a></li>
            <li class="breadcrumb-item active">Подарки {{ $user->login }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="container-fluid">
        @if ($gifts->isNotEmpty())
            <div class="row">
                @foreach($gifts as $gift)

                    <div class="col-md-4 col-sm-6">
                        <img src="{{ $gift->gift->path }}" alt="{{ $gift->gift->name }}"><br>
                        Отправил: {!! $gift->sendUser->getProfile() !!} ({{ dateFixed($gift->created_at) }})<br>

                        @if ($gift->text)
                            {{ $gift->text }}
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            {!! showError(trans('Gift::gifts.empty_gifts')) !!}
        @endif
    </div>
@stop
