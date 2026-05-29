@if ($photos->isNotEmpty())
    <div class="section-body">
    @foreach ($photos as $photo)
        @php
            $file = $photo->files()->first();
        @endphp

        @if ($file)
            <a href="{{ route('photos.view', ['id' => $photo->id]) }}">
                @if ($file->isVideo())
                    <video src="{{ $file->path }}" class="rounded" style="width: 100px;" preload="metadata"></video>
                @else
                    <img src="{{ $file->path }}" alt="{{ $file->name }}" class="rounded" style="width: 100px;">
                @endif
            </a>
        @endif
    @endforeach
    </div>
@endif
