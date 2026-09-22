{{-- Список провайдеров с привязкой/отвязкой: и на своей странице, и секцией в «Мои данные» --}}
@if (empty($availableProviders))
    <div class="alert alert-warning mb-0">{{ __('social_auth::social_auth.no_providers_enabled') }}</div>
@else
    <div class="list-group">
        @foreach ($availableProviders as $provider)
            @php($cfg = \Modules\SocialAuth\Models\Social::providerConfig($provider))
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <span>
                    <i class="{{ $cfg['icon'] }}" style="color: {{ $cfg['color'] }}"></i>
                    {{ $cfg['name'] }}
                </span>

                @isset($socials[$provider])
                    <form method="post" action="{{ route('social.unlink', ['provider' => $provider]) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">
                            {{ __('social_auth::social_auth.unlink') }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('social.link', ['provider' => $provider]) }}" class="btn btn-sm btn-outline-primary">
                        {{ __('social_auth::social_auth.link') }}
                    </a>
                @endisset
            </div>
        @endforeach
    </div>
@endif
