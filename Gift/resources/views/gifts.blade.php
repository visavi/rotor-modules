@extends('layout')

@section('title')
    {{ trans('Gift::gifts.title') }} {{ $user->login }}
@stop

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/gifts">{{ trans('Gift::gifts.title') }}</a></li>
            <li class="breadcrumb-item active">{{ trans('Gift::gifts.title') }} {{ $user->login }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="container-fluid">
        @if ($gifts->isNotEmpty())
            <div class="row">
                @foreach($gifts as $gift)

                    <div class="col-md-4 col-sm-6">
                        @if (isAdmin())
                            <div class="float-right">
                                <a href="/admin/gifts/delete?user={{ $gift->user->login }}&amp;id={{ $gift->id }}&amp;token={{ $_SESSION['token'] }}" onclick="return confirm('{{ trans('Gift::gifts.confirm_delete_gift') }}')" data-toggle="tooltip" title="{{ trans('main.delete') }}"><i class="fa fa-times text-muted"></i></a>
                            </div>
                        @endif

                        <img src="{{ $gift->gift->path }}" alt="{{ $gift->gift->name }}"><br>
                            {{ trans('main.sent') }}: {!! $gift->sendUser->getProfile() !!} ({{ dateFixed($gift->created_at) }})<br>

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
