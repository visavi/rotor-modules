{{-- Первая версия текста согласия: сохраняется в базу, дальше владелец правит её в админке.
     Язык — сайта, а не посетителя, который открыл страницу первым.
     Только теги, которые пропускает HtmlSanitizer. Пустой <p></p> между разделами —
     как двойной Enter в редакторе: у абзацев сообщения почти нет отступа --}}
<p>{{ __('privacy::privacy.intro', ['site' => $site, 'url' => $url, 'operator' => __('privacy::privacy.operator_placeholder', [], $locale)], $locale) }}</p>
@foreach (['data', 'purposes', 'actions', 'term'] as $part)
<p></p>
<p><strong>{{ __('privacy::privacy.' . $part . '_title', [], $locale) }}</strong><br>{{ __('privacy::privacy.' . $part, [], $locale) }}</p>
@endforeach
<p></p>
<p><strong>{{ __('privacy::privacy.withdraw_title', [], $locale) }}</strong><br>{{ __('privacy::privacy.withdraw', ['contact' => __('privacy::privacy.contact_placeholder', [], $locale)], $locale) }}</p>
