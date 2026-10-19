<div class="d-flex gap-2 mb-3">
    <a @class(['btn btn-sm', 'btn-primary' => request()->is('admin/paid-adverts/create'), 'btn-outline-primary' => ! request()->is('admin/paid-adverts/create')]) href="/admin/paid-adverts/create">{{ __('payment::payments.cards.tab_link') }}</a>
    <a @class(['btn btn-sm', 'btn-primary' => request()->is('admin/paid-adverts/create-card'), 'btn-outline-primary' => ! request()->is('admin/paid-adverts/create-card')]) href="/admin/paid-adverts/create-card">{{ __('payment::payments.cards.tab_card') }}</a>
</div>
