@extends('layout_rss')

@section('title', __('blog::blogs.title_rss'))

@section('content')
    @foreach ($articles as $article)
        <item>
            <title>{{ $article->title }}</title>
            <link>{{ route('articles.view', ['slug' => $article->slug]) }}</link>
            <description>{{ $article->getShareText() }}</description>
            <dc:creator>{{ $article->user->getName() }}</dc:creator>
            <pubDate>{{ date('r', $article->created_at) }}</pubDate>
            <category>{{ __('blog::blogs.blogs') }}</category>
            <guid>{{ route('articles.view', ['slug' => $article->slug]) }}</guid>
            @foreach ($article->getMedia() as $file)
                <media:content url="{{ $file->getUrl() }}" fileSize="{{ $file->size }}" type="{{ $file->mime_type }}" medium="{{ $file->isVideo() ? 'video' : 'image' }}" />
            @endforeach
        </item>
    @endforeach
@stop
