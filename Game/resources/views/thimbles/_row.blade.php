{{-- Ряд напёрстков: поднятые из $opens показываются поверх закрытых и через паузу закрываются обратно.
     Разметка ссылки идёт без переносов строк, иначе подчёркивание ссылки рисуется на пробелах между картинками --}}
@foreach ([1, 2, 3] as $number)
    <a class="thimble-link" href="/games/thimbles/go?thimble={{ $number }}&amp;rand={{ mt_rand(1000, 99999) }}">@isset($opens[$number])<span class="thimble-stack" style="--step: {{ $steps[$number] }}"><img class="thimble-closed" src="/assets/modules/games/thimbles/thimble.svg" width="86" height="74" alt="{{ __('game::games.thimbles') }}"><img class="thimble-open" src="/assets/modules/games/thimbles/{{ $opens[$number] }}.svg" width="86" height="74" alt="{{ __('game::games.thimbles') }}"></span>@else<img src="/assets/modules/games/thimbles/thimble.svg" width="86" height="74" alt="{{ __('game::games.thimbles') }}">@endisset</a>
@endforeach
