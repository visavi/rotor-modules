@extends('layout_rss')

@section('title', __('news::news.rss_title'))

@section('content')
    @foreach ($newses as $news)
        @php
            $newsText = absolutizeUrls((string) $news->getText());
        @endphp

        <item>
            <title>{{ $news->title }}</title>
            <link>{{ route('news.view', ['id' => $news->id]) }}</link>
            <description>{{ $newsText }}</description>
            <dc:creator>{{ $news->user->getName() }}</dc:creator>
            <pubDate>{{ date('r', $news->created_at) }}</pubDate>
            <category>{{ __('news::news.news') }}</category>
            <guid>{{ route('news.view', ['id' => $news->id]) }}</guid>
            @foreach ($news->files as $file)
                <media:content url="{{ $file->getUrl() }}" fileSize="{{ $file->size }}" type="{{ $file->mime_type }}" medium="image" />
            @endforeach
        </item>
    @endforeach
@stop
