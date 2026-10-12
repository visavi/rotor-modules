@once
<style>
    .zip-dir > summary { list-style: none; cursor: pointer; }
    .zip-dir > summary::-webkit-details-marker { display: none; }
    .zip-dir:not([open]) > summary .fa-folder-open,
    .zip-dir[open] > summary .fa-folder:not(.fa-folder-open) { display: none; }
</style>
@endonce

@foreach ($tree->dirs as $dirName => $subtree)
    <details class="zip-dir mt-1">
        <summary class="link-primary">
            <i class="far fa-folder text-warning"></i>
            <i class="far fa-folder-open text-warning"></i>
            <b>{{ $dirName }}</b>
            <span class="badge bg-adaptive ms-1">{{ $subtree->count }}</span>
        </summary>
        <div class="ms-3">
            @include('load::downs/_zip_tree', ['tree' => $subtree])
        </div>
    </details>
@endforeach

@foreach ($tree->files as $entry)
    <div>
        {{ icons($entry['ext']) }}
        <a href="{{ route('downs.zip-view', ['id' => $down->id, 'fid' => $file->id, 'zid' => $entry['index']]) }}">{{ $entry['basename'] }}</a>
        <small class="text-muted">({{ formatSize($entry['size']) }})</small>
    </div>
@endforeach
