<div class="section mb-3 shadow">
    <ol class="breadcrumb mb-1">
        <li class="breadcrumb-item">
            <i class="fa-regular fa-circle-question"></i> <a href="{{ route('offers.index') }}" class="text-muted">{{ __('offer::offers.section') }}</a>
        </li>

        <li class="breadcrumb-item">
            @if ($post->type === 'offer')
                <a href="{{ route('offers.index', ['type' => 'offer']) }}" class="text-muted">{{ __('offer::offers.offers') }}</a>
            @else
                <a href="{{ route('offers.index', ['type' => 'issue']) }}" class="text-muted">{{ __('offer::offers.problems') }}</a>
            @endif
        </li>
    </ol>

    <div class="section-header d-flex align-items-start">
        <div class="flex-grow-1">
            <div class="section-title">
                <h3><a class="post-title" href="{{ route('offers.view', ['id' => $post->id]) }}">{{ $post->title }}</a></h3>
            </div>
        </div>

        <div class="ms-2 flex-shrink-0">
            @include('app/_rating', ['model' => $post, 'vote' => $polls[$post::$morphName][$post->id] ?? null])
        </div>
    </div>

    <div class="section-content short-view">
        <div class="section-message">
            {{ $post->getText() }}
        </div>

        <div class="my-3">
            {{ $post->getStatus() }}
        </div>
    </div>

    <div class="section-body">
        <span class="avatar-micro">{{ $post->user->getAvatarImage() }}</span> {{ $post->user->getProfile() }}
        <small class="section-date text-muted fst-italic">{{ dateFixed($post->created_at) }}</small>
    </div>

    <i class="fa-regular fa-comment"></i> <a href="{{ route('offers.view', ['id' => $post->id]) }}#comments">{{ __('main.comments') }}</a> <span class="badge bg-adaptive">{{ $post->count_comments }}</span>
</div>
