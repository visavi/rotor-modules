@extends('layout')

@section('title', __('social_auth::social_auth.linked_accounts'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('users.user', ['login' => $user->login]) }}">{{ $user->getName() }}</a></li>
            <li class="breadcrumb-item active">{{ __('social_auth::social_auth.linked_accounts') }}</li>
        </ol>
    </nav>
@stop

@section('header')
    <h1>{{ __('social_auth::social_auth.linked_accounts') }}</h1>
@stop

@section('content')
    @include('social_auth::_accounts_list')
@stop
