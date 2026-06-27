@extends('layout_rss')

@section('title', __('forum::forums.title_rss'))

@section('content')
    @foreach ($topics as $topic)
        @if ($topic->lastPost->text)
            <item>
                <title>{{ $topic->title }}</title>
                <link>{{ route('topics.topic', ['id' => $topic->id]) }}</link>
                <description>{{ $topic->lastPost->getShareText() }}</description>
                <dc:creator>{{ $topic->lastPost->user->getName() }}</dc:creator>
                <pubDate>{{ $topic->updated_at?->format('r') }}</pubDate>
                <category>{{ __('forum::forums.topics') }}</category>
                <guid>{{ route('topics.topic', ['id' => $topic->id]) }}</guid>
                @foreach ($topic->lastPost->getMedia() as $file)
                    <media:content url="{{ $file->getUrl() }}" fileSize="{{ $file->size }}" type="{{ $file->mime_type }}" medium="{{ $file->isVideo() ? 'video' : 'image' }}" />
                @endforeach
            </item>
        @endif
    @endforeach
@stop
