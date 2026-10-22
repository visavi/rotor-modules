<form method="post">
    @csrf
    <div class="mb-3{{ hasError('category_id') }}">
        <label for="inputCategory" class="form-label">{{ __('blog::blogs.blog') }}:</label>

        <?php $inputCategory = (int) old('category_id', $article->category_id ?? $cid); ?>
        <select class="form-select" id="inputCategory" name="category_id">
            @foreach ($categories as $category)
                <option value="{{ $category->id }}"{{ ($inputCategory === $category->id && ! $category->closed) ? ' selected' : '' }}{{ $category->closed ? ' disabled' : '' }}>
                    {{ str_repeat('–', $category->depth) }} {{ $category->name }}
                </option>
            @endforeach
        </select>
        <div class="invalid-feedback">{{ textError('category_id') }}</div>
    </div>

    <div class="mb-3{{ hasError('title') }}">
        <label for="inputTitle" class="form-label">{{ __('blog::blogs.name') }}:</label>
        <input type="text" class="form-control" id="inputTitle" name="title" maxlength="{{ setting('blog_title_max') }}" value="{{ old('title', $article->title) }}" required>
        <div class="invalid-feedback">{{ textError('title') }}</div>
    </div>

    <div class="mb-3{{ hasError('text') }}">
        <label for="text" class="form-label">{{ __('blog::blogs.article') }}:</label>
        <textarea class="form-control tiptap" data-relate-type="{{ $article->getMorphClass() }}" data-relate-id="{{ $article->id ?? 0 }}" maxlength="{{ setting('blog_text_max') }}" id="text" rows="5" name="text" required>{{ old('text', $article->text) }}</textarea>
        <div class="invalid-feedback">{{ textError('text') }}</div>
        <span class="js-textarea-counter"></span>
    </div>

    <?php $inputTags = old('tags', $article->tags->pluck('name') ?? []); ?>
    <div class="mb-3{{ hasError('tags') }}">
        <label for="tags" class="form-label">{{ __('blog::blogs.tags') }}:</label>
        <select class="form-select input-tag" id="tags" name="tags[]" multiple required data-server="{{ route('blogs.tags-search') }}" data-max="10">
            <option disabled value="">{{ __('blog::blogs.tags') }}...</option>
            @foreach ($inputTags as $tag)
                <option value="{{ $tag }}" selected>{{ $tag }}</option>
            @endforeach
        </select>
        <div class="invalid-feedback">{{ textError('tags') }}</div>
    </div>

    @if (isAdmin())
        @php
            $isDelayed = old('delay', $article->published_at > now());
        @endphp
        <div class="form-check mb-3">
            {{-- Снятый флажок браузер не отправляет, без нуля запрос не узнает, что его сняли --}}
            <input type="hidden" name="delay" value="0">
            {{-- autocomplete="off": браузер не восстановит флажок после перезагрузки, иначе он разойдётся с блоком даты --}}
            <input type="checkbox" class="form-check-input" value="1" name="delay" id="delay" autocomplete="off" data-bs-toggle="collapse" data-bs-target="#delay-published" aria-controls="delay-published" aria-expanded="{{ $isDelayed ? 'true' : 'false' }}" @checked($isDelayed)>
            <label for="delay" class="form-check-label">{{ __('blog::blogs.delay_publication') }}</label>
        </div>

        <div class="collapse{{ $isDelayed ? ' show' : '' }}" id="delay-published">
            <div class="col-sm-6 col-md-4 mb-3">
                <label for="published_at" class="form-label">{{ __('blog::blogs.date_publication') }}</label>
                <input class="form-control{{ hasError('published_at') }}" type="datetime-local" name="published_at" id="published_at" value="{{ old('published_at', $article->published_at?->format('Y-m-d\TH:i')) }}">
                <div class="invalid-feedback">{{ textError('published_at') }}</div>
            </div>
        </div>
    @endif

    @include('app/_upload_media', ['model' => $article])

    <button class="btn btn-primary">
        {{ $article->exists ? __('main.change') : __('main.publish') }}
    </button>

    @if (! $article->exists)
        <button type="submit" class="btn btn-secondary" name="action" value="draft">
            {{ __('blog::blogs.add_draft') }}
        </button>
    @endif

    @if ($article->exists && ! $article->active)
        <button type="submit" class="btn btn-success" name="action" value="publish">
            {{ __('main.publish') }}
        </button>
    @endif
</form>
