@php
    /**
     * Кнопки «Поделиться»
     *
     * Обычные ссылки на share-эндпоинты соцсетей, внешних скриптов не требуют.
     * Заголовок и картинку соцсеть берет из og-тегов страницы
     *
     * @var string $shareUrl   Ссылка на запись
     * @var string $shareTitle Заголовок записи
     */
    $params = [
        '{url}'   => rawurlencode($shareUrl),
        '{title}' => rawurlencode($shareTitle),
    ];

    $networks = array_filter(
        config('share.networks'),
        static fn (string $key) => setting('share_' . $key),
        ARRAY_FILTER_USE_KEY,
    );

    $showCopy = (bool) setting('share_copy');
@endphp

@if ($networks || $showCopy)
    <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
        <span class="text-muted small"><i class="fas fa-share-nodes"></i> {{ __('share::share.share') }}:</span>

        @foreach ($networks as $network)
            <a class="fs-4 lh-1 text-decoration-none link-opacity-75-hover"
               style="color: {{ $network['color'] }}"
               href="{{ strtr($network['link'], $params) }}"
               target="_blank"
               rel="noopener nofollow"
               title="{{ $network['name'] }}"
               aria-label="{{ $network['name'] }}">
                <i class="{{ $network['icon'] }}"></i>
            </a>
        @endforeach

        @if ($showCopy)
            <a class="fs-4 lh-1 text-decoration-none link-opacity-75-hover text-body-secondary"
               href="{{ $shareUrl }}"
               onclick="copyToClipboard(this); return false"
               data-copy="{{ $shareUrl }}"
               title="{{ __('main.copy_link') }}"
               aria-label="{{ __('main.copy_link') }}">
                <i class="fas fa-link"></i>
            </a>
        @endif
    </div>
@endif
