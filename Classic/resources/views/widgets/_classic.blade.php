<div class="section mb-3 shadow">
    <div class="section-title">
        <i class="far fa-circle text-muted"></i>
        <a href="{{ route('news.index') }}" class="">{{ __('index.news') }}</a>
        <span class="badge bg-adaptive">{{ statsNewsDate() }}</span>
    </div>
    {{ pinnedNews() }}
</div>

<div class="section mb-3 shadow">
    <div class="section-title">
        <i class="fa fa-comment fa-lg text-muted"></i>
        <a href="/pages/recent">{{ __('index.communication') }}</a>
    </div>
    <div class="section-body">
        <i class="far fa-circle text-muted"></i> <a href="{{ route('guestbook.index') }}">{{ __('index.guestbook') }}</a> <span class="badge bg-adaptive">{{ statsGuestbook() }}</span><br>
        @if(Route::has('photos.index'))<i class="far fa-circle text-muted"></i> <a href="{{ route('photos.index') }}">{{ __('index.photos') }}</a> <span class="badge bg-adaptive">{{ statsPhotos() }}</span><br>@endif
        <i class="far fa-circle text-muted"></i> <a href="{{ route('votes.index') }}">{{ __('index.votes') }}</a> <span class="badge bg-adaptive">{{ statVotes() }}</span><br>
        @hook('classicWidgetLinks')
    </div>
</div>

<div class="section mb-3 shadow">
    <div class="section-title">
        <i class="fab fa-forumbee fa-lg text-muted"></i>
        <a href="{{ route('forums.index') }}">{{ __('index.forums') }}</a>
        <span class="badge bg-adaptive">{{ statsForum() }}</span>
    </div>
    {{ recentTopics() }}
</div>

<div class="section mb-3 shadow">
    <div class="section-title">
        <i class="fa fa-download fa-lg text-muted"></i>
        <a href="{{ route('loads.index') }}">{{ __('index.loads') }}</a>
        <span class="badge bg-adaptive">{{ statsLoad() }}</span>
    </div>
    {{ recentDowns() }}
</div>

<div class="section mb-3 shadow">
    <div class="section-title">
        <i class="fa fa-globe fa-lg text-muted"></i>
        <a href="{{ route('blogs.index') }}">{{ __('index.blogs') }}</a>
        <span class="badge bg-adaptive">{{ statsBlog() }}</span>
    </div>
    {{ recentArticles() }}
</div>

@hook('classicSections')

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
