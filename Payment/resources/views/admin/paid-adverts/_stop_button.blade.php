{{-- Снятие с показа из формы правки: отправляет ту же форму на другой адрес, поля не проверяются и не сохраняются --}}
@if ($advert->exists && $advert->deleted_at?->gt(now()))
    <button class="btn btn-outline-danger" formaction="/admin/paid-adverts/stop/{{ $advert->id }}" formnovalidate onclick="return confirm('{{ __('payment::payments.paid_adverts.confirm_stop') }}')">{{ __('payment::payments.paid_adverts.stop') }}</button>
@endif
