@extends('layout_rss')

@section('title', __('forum::forums.title_rss'))

@section('content')
    @foreach ($topics as $topic)
        @if ($topic->lastPost->text)
            @php
                $postText = absolutizeUrls((string) $topic->lastPost->getText());
            @endphp

            <item>
                <title>{{ $topic->title }}</title>
                <link>{{ route('topics.topic', ['id' => $topic->id]) }}</link>
                <description>{{ $postText }}</description>
                <dc:creator>{{ $topic->lastPost->user->getName() }}</dc:creator>
                <pubDate>{{ date('r', $topic->updated_at) }}</pubDate>
                <category>{{ __('forum::forums.topics') }}</category>
                <guid>{{ route('topics.topic', ['id' => $topic->id]) }}</guid>
                @foreach ($topic->lastPost->files as $file)
                    <media:content url="{{ $file->getUrl() }}" fileSize="{{ $file->size }}" type="{{ $file->mime_type }}" medium="image" />
                @endforeach
            </item>
        @endif
    @endforeach
@stop
