<div class="d-flex gap-2 mb-3">
    <a @class(['btn btn-sm', 'btn-primary' => request()->is('payments/advert'), 'btn-outline-primary' => ! request()->is('payments/advert')]) href="/payments/advert">{{ __('payment::payments.cards.tab_link') }}</a>
    <a @class(['btn btn-sm', 'btn-primary' => request()->is('payments/card'), 'btn-outline-primary' => ! request()->is('payments/card')]) href="/payments/card">{{ __('payment::payments.cards.tab_card') }}</a>
</div>

@if (auth()->guest() && request()->is('payments/advert'))
    <div class="form-text mb-3">{{ __('payment::payments.cards.login_required') }}</div>
@endif
