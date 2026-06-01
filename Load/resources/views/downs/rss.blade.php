@extends('layout_rss')

@section('title', __('load::loads.rss_downs'))

@section('content')
    @foreach ($downs as $down)
        <?php $downText = absolutizeUrls((string) $down->getText()); ?>

        <item>
            <title>{{ $down->title }}</title>
            <link>{{ route('downs.view', ['id' => $down->id]) }}</link>
            <description>{{ $downText }}</description>
            <dc:creator>{{ $down->user->getName() }}</dc:creator>
            <pubDate>{{ date('r', $down->created_at) }}</pubDate>
            <category>{{ __('load::loads.loads') }}</category>
            <guid>{{ route('downs.view', ['id' => $down->id]) }}</guid>
            @foreach ($down->getMedia() as $file)
                <media:content url="{{ $file->getUrl() }}" fileSize="{{ $file->size }}" type="{{ $file->mime_type }}" medium="{{ $file->isVideo() ? 'video' : 'image' }}" />
            @endforeach
        </item>
    @endforeach
@stop
