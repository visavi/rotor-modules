@if(Route::has('news.index'))
    <div class="section mb-3 shadow">
        <div class="section-title">
            <i class="far fa-circle text-muted"></i>
            <a href="{{ route('news.index') }}" class="">{{ __('index.news') }}</a>
            <span class="badge bg-adaptive">{{ statsNewsDate() }}</span>
        </div>
        {{ pinnedNews() }}
    </div>
@endif

<div class="section mb-3 shadow">
    <div class="section-title">
        <i class="fa fa-comment fa-lg text-muted"></i>
        <a href="{{ route('classic.recent') }}">{{ __('index.communication') }}</a>
    </div>
    <div class="section-body">
        @if(Route::has('guestbook.index'))
            <i class="far fa-circle text-muted"></i> <a href="{{ route('guestbook.index') }}">{{ __('index.guestbook') }}</a> <span class="badge bg-adaptive">{{ statsGuestbook() }}</span><br>
        @endif
        @if(Route::has('photos.index'))
            <i class="far fa-circle text-muted"></i> <a href="{{ route('photos.index') }}">{{ __('index.photos') }}</a> <span class="badge bg-adaptive">{{ statsPhotos() }}</span><br>
        @endif
    </div>
</div>

@if(Route::has('forums.index'))
    <div class="section mb-3 shadow">
        <div class="section-title">
            <i class="fab fa-forumbee fa-lg text-muted"></i>
            <a href="{{ route('forums.index') }}">{{ __('index.forums') }}</a>
            <span class="badge bg-adaptive">{{ statsForum() }}</span>
        </div>
        {{ recentTopics() }}
    </div>
@endif

@if(Route::has('loads.index'))
    <div class="section mb-3 shadow">
        <div class="section-title">
            <i class="fa fa-download fa-lg text-muted"></i>
            <a href="{{ route('loads.index') }}">{{ __('index.loads') }}</a>
            <span class="badge bg-adaptive">{{ statsLoad() }}</span>
        </div>
        {{ recentDowns() }}
    </div>
@endif

@if(Route::has('blogs.index'))
    <div class="section mb-3 shadow">
        <div class="section-title">
            <i class="fa fa-globe fa-lg text-muted"></i>
            <a href="{{ route('blogs.index') }}">{{ __('index.blogs') }}</a>
            <span class="badge bg-adaptive">{{ statsBlog() }}</span>
        </div>
        {{ recentArticles() }}
    </div>
@endif

<div class="row">
    <div class="col-md-6">
        <div class="section mb-3 shadow">
            <div class="section-title">
                <i class="fa fa-chart-line fa-lg text-muted"></i>
                {{ __('index.courses') }}
            </div>
            <div class="section-body">
                {{ getCourses() }}
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="section mb-3 shadow">
            <div class="section-title">
                <i class="fa fa-calendar-alt fa-lg text-muted"></i>
                {{ __('index.calendar') }}
            </div>
            <div class="section-body">
                {{ getCalendar() }}
            </div>
        </div>
    </div>
</div>

<div class="section mb-3 shadow">
    <div class="section-title">
        <i class="fa fa-users fa-lg text-muted"></i>
        {{ __('classic::classic.who_online') }}
    </div>
    <div class="section-body">
        {{ onlineWidget() }}
    </div>
</div>
