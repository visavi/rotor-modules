<form method="post">
    @csrf
    <div class="mb-3{{ hasError('place') }}">
        <label for="place" class="form-label">{{ __('admin.paid_adverts.place') }}:</label>

        <?php $inputPlace = getInput('place', $place ?? $advert->place); ?>
        <select class="form-select" name="place" id="place">
            @foreach ($places as $place)
                <?php $selected = ($place === $inputPlace) ? ' selected' : ''; ?>
                <option value="{{ $place }}"{{ $selected }}>{{ __('admin.paid_adverts.' . $place) }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback">{{ textError('place') }}</div>
    </div>

    <div class="mb-3{{ hasError('site') }}">
        <label for="site" class="form-label">{{ __('admin.paid_adverts.link') }}:</label>
        <input name="site" class="form-control" id="site" maxlength="100" placeholder="{{ __('admin.paid_adverts.link') }}" value="{{ getInput('site', $advert->site) }}" required>
        <div class="invalid-feedback">{{ textError('site') }}</div>
    </div>

    <div class="mb-3{{ hasError('names') }}">
        <div class="js-advert-list">
            <?php $names = array_values(array_diff((array) getInput('names', $advert->names), [''])) ?>

            @for ($i = 0; $i < max(1, count($names)); $i++)
                @if ($i === 0)
                    <label for="names{{ $i }}">{{ __('admin.paid_adverts.names') }}:</label>
                    <a class="js-advert-add" href="#" data-bs-toggle="tooltip" title="{{ __('main.add') }}"><i class="fas fa-plus-square"></i></a>
                    <input type="text" name="names[]" class="form-control" id="names{{ $i }}" value="{{ $names[$i] ?? '' }}" maxlength="35" placeholder="{{ __('admin.paid_adverts.name') }}">
                @else
                    <div class="input-group mt-1 js-advert-append">
                        <input class="form-control" name="names[]" type="text" value="{{ $names[$i] ?? '' }}" maxlength="35" placeholder="{{ __('admin.paid_adverts.name') }}">
                        <span class="input-group-text">
                            <a class="js-advert-remove" href="#"><i class="fa fa-times"></i></a>
                        </span>
                    </div>
                @endif
            @endfor
        </div>
        <div class="invalid-feedback">{{ textError('names') }}</div>
    </div>

    <?php $color = getInput('color', $advert->color); ?>
    <div class="col-sm-4 mb-3{{ hasError('color') }}">
        <label for="color" class="form-label">{{ __('admin.paid_adverts.color') }}:</label>
        <div class="input-group">
            <input type="text" name="color" class="form-control colorpicker" id="color" maxlength="7" value="{{ $color }}">
            <input type="color" class="form-control form-control-color colorpicker-addon" id="color-picker" value="{{ $color }}">
        </div>
        <div class="invalid-feedback">{{ textError('color') }}</div>
    </div>

    <div class="form-check">
        <input type="hidden" value="0" name="bold">
        <input type="checkbox" class="form-check-input js-bold" value="1" name="bold" id="bold"{{ getInput('bold', $advert->bold) ? ' checked' : '' }}>
        <label class="form-check-label" for="bold">{{ __('admin.paid_adverts.bold') }}</label>
    </div>


   {{-- <?php $inputDay = getInput('day', $advert->day); ?>
    <div class="col-sm-4 mb-3{{ hasError('period') }}">
        <label for="period" class="form-label">Срок:</label>
        <select class="form-select" id="period" name="period">
            @foreach ($days as $day)
                <?php $selected = ($day === $inputDay) ? ' selected' : ''; ?>
                <option value="{{ $day }}"{{ $selected }}>
                    {{ formatTime($day * 86400) }}
                </option>
            @endforeach
        </select>
        <div class="invalid-feedback">{{ textError('period') }}</div>
    </div>--}}

    @if (! $advert->id)
        <div class="col-sm-4 mb-3{{ hasError('term') }}">
            <label for="term" class="form-label">{{ __('admin.paid_adverts.term') }}:</label>
            <input class="form-control" type="date" name="term" id="term" value="{{ getInput('term', dateFixed($advert->deleted_at, 'Y-m-d')) }}" min="{{ dateFixed(SITETIME + 86400, 'Y-m-d') }}" required>
            <div class="invalid-feedback">{{ textError('term') }}</div>
            {{ getInput('term', dateFixed($advert->deleted_at, 'Y-m-d')) }}
        </div>
    @endif

    <button class="btn btn-primary">{{ $advert->id ? __('main.save') : 'Купить' }}</button>
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
