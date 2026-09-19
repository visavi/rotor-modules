@use('Modules\SocialAuth\Models\Social')

@extends('layout')

@section('title', __('social_auth::social_auth.socials'))

@section('breadcrumb')
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.index') }}">{{ __('index.panel') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.index') }}">{{ __('index.modules') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.modules.module', ['module' => 'SocialAuth']) }}">{{ __('admin.modules.module') }} {{ __('social_auth::social_auth.module_name') }}</a></li>
            <li class="breadcrumb-item active">{{ __('social_auth::social_auth.socials') }}</li>
        </ol>
    </nav>
@stop

@section('header')
    <h1>{{ __('social_auth::social_auth.socials') }}</h1>
@stop

@section('content')
    <div class="row row-cols-2 row-cols-md-4 g-2 mb-3">
        @foreach ($providers as $key => $config)
            <div class="col">
                <a class="card h-100 text-decoration-none{{ $provider === $key ? ' border-primary' : '' }}"
                   href="{{ route('social_auth.socials', ['provider' => $provider === $key ? null : $key]) }}">
                    <div class="card-body text-center">
                        <i class="{{ $config['icon'] }}" style="color: {{ $config['color'] }}"></i>
                        <div class="fw-bold mt-2">{{ $config['name'] }}</div>
                        <div class="h4 mb-0">{{ (int) ($stats[$key]->total ?? 0) }}</div>
                        <small class="text-muted">
                            {{ __('social_auth::social_auth.active_month') }}: {{ (int) ($stats[$key]->active ?? 0) }}
                        </small>
                    </div>
                </a>
            </div>
        @endforeach
    </div>

    @if ($provider)
        <div class="mb-3">
            <a class="btn btn-sm btn-secondary" href="{{ route('social_auth.socials') }}">
                <i class="fas fa-times"></i> {{ __('social_auth::social_auth.reset_filter') }}
            </a>
        </div>
    @endif

    @if ($socials->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>{{ __('main.user') }}</th>
                        <th>{{ __('social_auth::social_auth.provider') }}</th>
                        <th>{{ __('social_auth::social_auth.linked_at') }}</th>
                        <th>{{ __('social_auth::social_auth.last_login_at') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($socials as $social)
                        @php $config = Social::providerConfig($social->provider) @endphp
                        <tr>
                            <td>
                                @if ($social->user)
                                    {{ $social->user->getProfile() }}
                                @else
                                    <span class="text-muted">{{ __('social_auth::social_auth.user_deleted') }}</span>
                                @endif
                            </td>
                            <td>
                                <i class="{{ $config['icon'] }} fa-1x" style="color: {{ $config['color'] }}"></i>
                                {{ $config['name'] }}
                            </td>
                            <td class="text-nowrap">{{ dateFixed($social->created_at) }}</td>
                            <td class="text-nowrap">
                                @if ($social->last_login_at)
                                    {{ dateFixed($social->last_login_at) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $socials->links() }}
    @else
        {{ showError(__('social_auth::social_auth.empty_socials')) }}
    @endif
@stop
