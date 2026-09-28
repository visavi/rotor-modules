{{-- Галочки мессенджеров к номеру. Выключенные в настройках не показываются,
     но прежние отметки уходят скрытыми полями: включат снова — вернутся --}}
@php($enabled = Modules\Board\Models\Item::enabledMessengers())
<div class="mb-3">
    @foreach (Modules\Board\Models\Item::MESSENGERS as $key => $messenger)
        @if (isset($enabled[$key]))
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="messengers[]" value="{{ $key }}" id="messenger_{{ $key }}"{{ in_array($key, $selected, true) ? ' checked' : '' }}>
                <label class="form-check-label" for="messenger_{{ $key }}">@include('board::boards/_messenger_icon', ['withLabel' => true])</label>
            </div>
        @elseif (in_array($key, $selected, true))
            <input type="hidden" name="messengers[]" value="{{ $key }}">
        @endif
    @endforeach

    @if ($enabled)
        <div class="form-text">{{ __('board::boards.messengers_hint') }}</div>
    @endif
</div>
