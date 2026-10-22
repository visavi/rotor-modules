{{-- Снятие с показа из списка: $data — действующая реклама --}}
@if ($data->deleted_at?->gt(now()))
    <form action="/admin/paid-adverts/stop/{{ $data->id }}" method="post" class="d-inline" onsubmit="return confirmAction(this)" data-confirm="{{ __('payment::payments.paid_adverts.confirm_stop') }}">
        @csrf
        <button class="btn btn-link p-0" title="{{ __('payment::payments.paid_adverts.stop') }}"><i class="fas fa-power-off text-muted"></i></button>
    </form>
@endif
