@use('Illuminate\Support\Facades\Cookie')

@php
    // Свёрнута, пока нечего показывать: после ошибки или с прикреплёнными файлами форма открыта
    $compact = ! $errors->any() && ! old('text') && (auth()->guest() || $files->isEmpty());
@endphp

@if (auth()->check())
    <div class="section-form mb-3 shadow">
        <form action="{{ route('guestbook.create') }}" method="post"@if ($compact) data-compact @endif>
            @csrf
            <div class="mb-3 compact-field{{ hasError('text') }}">
                <textarea class="form-control tiptap" maxlength="{{ setting('guestbook_text_max') }}" id="text" rows="5" name="text" data-relate-type="{{ \Modules\Guestbook\Models\Guestbook::$morphName }}" data-relate-id="0" placeholder="{{ __('main.write_message') }}" required>{{ old('text') }}</textarea>
                <div class="invalid-feedback">{{ textError('text') }}</div>
                <span class="js-textarea-counter"></span>
            </div>

            @include('app/_upload_file', [
                'model' => \Modules\Guestbook\Models\Guestbook::getModel(),
                'files' => $files,
            ])

            <button class="btn btn-primary">{{ __('main.write') }}</button>
        </form>
    </div>

@elseif (setting('bookadds'))
    <div class="section-form mb-3 shadow">
        <form action="{{ route('guestbook.create') }}" method="post"@if ($compact) data-compact @endif>
            @csrf
            {{-- Имя под сообщением: при раскрытии над полем с курсором ничего не появляется --}}
            <div class="mb-3 compact-field{{ hasError('text') }}">
                <textarea class="form-control" id="text" rows="5" maxlength="{{ setting('guestbook_text_max') }}" name="text" placeholder="{{ __('main.write_message') }}" required>{{ old('text') }}</textarea>
                <div class="invalid-feedback">{{ textError('text') }}</div>
            </div>

            <div class="mb-3{{ hasError('guest_name') }}">
                <label for="inputName" class="form-label">{{ __('users.name') }}:</label>
                <input class="form-control" id="inputName" name="guest_name" maxlength="20" value="{{ old('guest_name', Cookie::get('guest_name')) }}">
                <div class="invalid-feedback">{{ textError('guest_name') }}</div>
            </div>

            {{ getCaptcha() }}
            <button class="btn btn-primary">{{ __('main.write') }}</button>
        </form>
    </div>
@else
    {{ showError(__('main.not_authorized')) }}
@endif
