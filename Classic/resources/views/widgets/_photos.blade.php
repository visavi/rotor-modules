@use('App\Services\ThumbnailService')

@if ($photos->isNotEmpty())
    <div class="section-body">
    @foreach ($photos as $photo)
        @php
            // Файлы уже загружены вместе с фото: files() со скобками — лишний запрос на каждое
            $file = $photo->files->first();
        @endphp

        @if ($file)
            <a href="{{ route('photos.view', ['id' => $photo->id]) }}">
                @if ($file->isVideo())
                    <video src="{{ $file->path }}" class="rounded" style="width: 100px;" preload="metadata"></video>
                @else
                    <img src="{{ $file->thumbnail(ThumbnailService::SQUARE) }}" alt="{{ $file->name }}" class="rounded" style="width: 100px;">
                @endif
            </a>
        @endif
    @endforeach
    </div>
@endif
