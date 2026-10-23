<div class="text-center">
    {!! $result !!}

    @if (auth()->check())
        @if ($result)
            <small><a href="/adverts" rel="nofollow">[+]</a></small>
        @else
            <small><a href="/adverts/create" rel="nofollow">{{ __('advert::adverts.create_advert') }}</a></small>
        @endif
    @endif
</div>
