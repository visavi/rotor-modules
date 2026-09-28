{{-- Свободный ввод с подсказками из городов других объявлений.
     data-max="1" прячет поле ввода, пока город выбран: сменить — через крестик --}}
<div class="mb-3{{ hasError('city') }}">
    <label for="inputCity" class="form-label">{{ __('board::boards.city') }}:</label>
    <select class="form-select input-tag" id="inputCity" name="city" data-server="{{ route('boards.cities') }}" data-max="1" data-placeholder="{{ __('board::boards.city_placeholder') }}">
        {{-- Без пустой опции одиночный select после крестика отправил бы прежний город --}}
        <option value=""></option>
        @if ($city)
            <option value="{{ $city }}" selected>{{ $city }}</option>
        @endif
    </select>
    <div class="invalid-feedback">{{ textError('city') }}</div>
</div>
