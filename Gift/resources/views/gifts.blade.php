@extends('layout')

@section('title', __('Gift::gifts.title') . ' ' . $user->getName())

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="/gifts">{{ __('Gift::gifts.title') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Gift::gifts.title') }} {{ $user->getName() }}</li>
        </ol>
    </nav>
@stop

@section('content')
    <div class="container-fluid">
        @if ($gifts->isNotEmpty())
            <div class="row">
                @foreach ($gifts as $gift)

                    <div class="col-md-4 col-sm-6">
                        @if (isAdmin())
                            <div class="float-end">
                                <a href="/admin/gifts/delete?user={{ $gift->user->login }}&amp;id={{ $gift->id }}&amp;token={{ $_SESSION['token'] }}" onclick="return confirm('{{ __('Gift::gifts.confirm_delete_gift') }}')" data-bs-toggle="tooltip" title="{{ __('main.delete') }}"><i class="fa fa-times text-muted"></i></a>
                            </div>
                        @endif

                        <img src="{{ $gift->gift->path }}" alt="{{ $gift->gift->name }}"><br>
                            {{ __('main.sent') }}: {{ $gift->sendUser->getProfile() }}
                            <small class="section-date text-muted fst-italic">{{ dateFixed($gift->created_at) }}</small><br>

                        @if ($gift->text)
                            {{ bbCode($gift->text) }}
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            {{ showError(__('Gift::gifts.empty_gifts')) }}
        @endif
    </div>
@stop
