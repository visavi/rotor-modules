<div class="section mb-3 shadow">
    <ol class="breadcrumb mb-1">
        <li class="breadcrumb-item">
            <i class="fa-solid fa-rectangle-list"></i>
            <a href="{{ route('boards.index') }}" class="text-muted">{{ __('board::boards.boards') }}</a>
        </li>

        @if ($post->category->parent->id)
            <li class="breadcrumb-item">
                <a href="{{ route('boards.index', ['id' => $post->category->parent->id]) }}" class="text-muted">{{ $post->category->parent->name }}</a>
            </li>
        @endif

        <li class="breadcrumb-item">
            <a href="{{ route('boards.index', ['id' => $post->category->id]) }}" class="text-muted">{{ $post->category->name }}</a>
        </li>
    </ol>

    <h3><a class="post-title" href="{{ route('items.view', ['id' => $post->id]) }}">{{ $post->title }}</a></h3>

    <div class="section-content short-view col-md-12">
        <div class="section-message">{{ $post->getText() }}</div>
    </div>

    <div class="section-body">
        <span class="avatar-micro">{{ $post->user->getAvatarImage() }}</span>
        {{ $post->user->getProfile() }}
        <small class="section-date text-muted fst-italic">{{ dateFixed($post->updated_at) }}</small>
    </div>
</div>
