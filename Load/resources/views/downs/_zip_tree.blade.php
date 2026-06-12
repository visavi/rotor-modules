@once
<style>
    .zip-dir a.collapsed .fa-folder-open { display: none; }
    .zip-dir a:not(.collapsed) .fa-folder:not(.fa-folder-open) { display: none; }
</style>
@endonce

@foreach ($tree->dirs as $dirName => $subtree)
    @php $collapseId = 'zd_' . uniqid() @endphp
    <div class="zip-dir mt-1">
        <a class="text-decoration-none collapsed" data-bs-toggle="collapse" href="#{{ $collapseId }}" role="button">
            <i class="far fa-folder text-warning"></i>
            <i class="far fa-folder-open text-warning"></i>
            <b>{{ $dirName }}</b>
            <span class="badge bg-adaptive ms-1">{{ $subtree->count }}</span>
        </a>
        <div class="collapse ms-3" id="{{ $collapseId }}">
            @include('load::downs/_zip_tree', ['tree' => $subtree])
        </div>
    </div>
@endforeach

@foreach ($tree->files as $entry)
    <div>
        {{ icons($entry['ext']) }}
        <a href="{{ route('downs.zip-view', ['id' => $down->id, 'fid' => $file->id, 'zid' => $entry['index']]) }}">{{ $entry['basename'] }}</a>
        <small class="text-muted">({{ formatSize($entry['size']) }})</small>
    </div>
@endforeach
