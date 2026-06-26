@extends('layout_rss')

@section('title', __('news::news.rss_title'))

@section('content')
    @foreach ($newses as $news)
        <item>
            <title>{{ $news->title }}</title>
            <link>{{ route('news.view', ['id' => $news->id]) }}</link>
            <description>{{ $news->getShareText() }}</description>
            <dc:creator>{{ $news->user->getName() }}</dc:creator>
            <pubDate>{{ $news->created_at->format('r') }}</pubDate>
            <category>{{ __('news::news.news') }}</category>
            <guid>{{ route('news.view', ['id' => $news->id]) }}</guid>
            @if ($enclosure = $news->getDetachedMedia()->first())
                <enclosure url="{{ $enclosure->getUrl() }}" length="{{ $enclosure->size }}" type="{{ $enclosure->mime_type }}" />
            @endif
        </item>
    @endforeach
@stop
