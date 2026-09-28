{{-- Значок мессенджера; без иконки Font Awesome — плашка с названием, подпись ей не нужна --}}
@if ($messenger['icon'])
    <i class="{{ $messenger['icon'] }}" style="color: {{ $messenger['color'] }}"></i>@if ($withLabel ?? false) {{ $messenger['label'] }}@endif
@else
    <span class="badge rounded-pill" style="background-color: {{ $messenger['color'] }}">{{ $messenger['label'] }}</span>
@endif
