<div class="section mb-3 shadow">
    <ol class="breadcrumb mb-1">
        <li class="breadcrumb-item">
            <i class="fa-regular fa-image"></i> <a href="{{ route('photos.index') }}" class="text-muted">{{ __('photo::photos.photos') }}</a>
        </li>
    </ol>

    <div class="section-header d-flex align-items-start">
        <div class="flex-grow-1">
            <div class="section-title">
                <h3><a class="post-title" href="{{ route('photos.view', ['id' => $post->id]) }}">{{ $post->title }}</a></h3>
            </div>
        </div>

        <div class="ms-2 flex-shrink-0">
            @include('app/_rating', ['model' => $post, 'vote' => $polls[$post::$morphName][$post->id] ?? null])
        </div>
    </div>

    <div class="section-content short-view">
        @include('app/_media_slider', ['model' => $post])

        @if ($post->text)
            <div class="section-message">
                {{ $post->getText() }}
            </div>
        @endif
    </div>

    <div class="section-body">
        <span class="avatar-micro">{{ $post->user->getAvatarImage() }}</span> {{ $post->user->getProfile() }}
        <small class="section-date text-muted fst-italic">{{ dateFixed($post->created_at) }}</small>
    </div>

    <i class="fa-regular fa-comment"></i> <a href="{{ route('photos.view', ['id' => $post->id]) }}#comments">{{ __('main.comments') }}</a> <span class="badge bg-adaptive">{{ $post->count_comments }}</span>
</div>
