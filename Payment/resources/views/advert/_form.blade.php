<form action="/payments/calculate" method="post">
    @csrf
    <div class="mb-3">
        <label for="place" class="form-label">{{ __('admin.paid_adverts.place') }}:</label>

        @php $inputPlace = old('place', $advert->place ?? null); @endphp
        <select class="form-select{{ hasError('place') }}" name="place" id="place">
            @foreach ($places as $place => $name)
                @php $selected = ($place === $inputPlace) ? ' selected' : ''; @endphp
                <option value="{{ $place }}"{{ $selected }}>{{ $name }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback">{{ textError('place') }}</div>
    </div>

    <div class="mb-3">
        <label for="site" class="form-label">{{ __('admin.paid_adverts.link') }}:</label>
        <input class="form-control{{ hasError('site') }}" id="site" name="site" type="text" value="{{ old('site', $advert->site) }}" maxlength="100" required>
        <div class="invalid-feedback">{{ textError('site') }}</div>
    </div>

    <div class="mb-3">
        <div class="js-advert-list">
            @php $names = (array) old('names', $advert->names) @endphp

            @for ($i = 0; $i < max(1, count($names)); $i++)
                @if ($i === 0)
                    <label for="names{{ $i }}">{{ __('admin.paid_adverts.names') }}:</label>
                    <a class="js-advert-add" href="#" data-bs-toggle="tooltip" title="{{ __('main.add') }}"><i class="fas fa-plus-square"></i></a>
                    <input type="text" name="names[]" class="form-control{{ hasError('names.' . $i) }}" id="names{{ $i }}" value="{{ $names[$i] ?? '' }}" maxlength="35" placeholder="{{ __('admin.paid_adverts.name') }}" required>
                    <div class="invalid-feedback">{{ textError('names.' . $i) }}</div>
                @else
                    <div class="input-group mt-1 js-advert-append">
                        <input class="form-control{{ hasError('names.'. $i) }}" name="names[]" type="text" value="{{ $names[$i] ?? '' }}" maxlength="35" placeholder="{{ __('admin.paid_adverts.name') }}">
                        <span class="input-group-text">
                            <a class="js-advert-remove" href="#"><i class="fa fa-times"></i></a>
                        </span>
                        <div class="invalid-feedback">{{ textError('names.' . $i) }}</div>
                    </div>
                @endif
            @endfor
        </div>
    </div>

    @php $color = old('color', $advert->color); @endphp
    <div class="col-sm-4 mb-3">
        <label for="color" class="form-label">{{ __('admin.paid_adverts.color') }}:</label>
        <div class="input-group">
            <input type="text" name="color" class="form-control{{ hasError('color') }} colorpicker" id="color" maxlength="7" value="{{ $color }}">
            <input type="color" class="form-control form-control-color colorpicker-addon" value="{{ $color }}">
            <div class="invalid-feedback">{{ textError('color') }}</div>
        </div>
    </div>

    <div class="form-check form-switch">
        <input type="hidden" value="0" name="bold">
        <input type="checkbox" class="form-check-input" value="1" name="bold" id="bold"{{ old('bold', $advert->bold) ? ' checked' : '' }}>
        <label class="form-check-label" for="bold">{{ __('admin.paid_adverts.bold') }}</label>
    </div>

    @if (! $advert->id)
        <div class="col-sm-4 mb-3">
            <label for="term" class="form-label">{{ __('admin.paid_adverts.term') }}:</label>
            <input class="form-control{{ hasError('term') }}" type="number" name="term" id="term" value="{{ old('term', 10) }}" min="1" required>
            <div class="invalid-feedback">{{ textError('term') }}</div>
        </div>
    @endif

    <div class="mb-3">
        <label for="message" class="form-label">{{ __('main.comment') }}:</label>
        <textarea class="form-control{{ hasError('comment') }} markItUp" id="comment" rows="5" name="comment">{{ old('comment', $advert->comment) }}</textarea>
        <div class="invalid-feedback">{{ textError('comment') }}</div>
    </div>

    <button class="btn btn-primary">{{ $advert->id ? __('main.save') : __('payment::payments.place_order') }}</button>
</form>

@push('scripts')
    <script>
        $(".js-advert-add").click(function () {
            $('.js-advert-list').append('<div class="input-group mt-1 js-advert-append">' +
                '<input class="form-control" name="names[]" type="text" value="" maxlength="35" placeholder="<?= __('admin.paid_adverts.name') ?>">' +
                '<span class="input-group-text">' +
                    '<a class="js-advert-remove" href="#"><i class="fa fa-times"></i></a>' +
                '</span>' +
            '</div>');

            return false;
        });

        $(document).on('click', '.js-advert-remove', function () {
            $(this).closest('.js-advert-append').remove();

            return false;
        });
    </script>
@endpush
