<?php

use App\Models\User;
use App\Support\Hook;
use App\Support\Registry;
use Illuminate\Http\Request;
use Illuminate\Validation\Validator;
use Modules\Privacy\Models\PrivacyConsent;
use Modules\Privacy\Models\PrivacyText;

// Галочка согласия в форме регистрации
Hook::add('registerFields', static fn () => view('privacy::_checkbox'));

// Вход через соцсеть заводит аккаунт без формы: согласие — по пометке под кнопками
Hook::add('socialAuthNotice', static fn () => view('privacy::_social_notice'));

// Без согласия регистрация не проходит — и на сайте, и в API
Registry::onRegisterValidate(static function (Request $request, Validator $validator): void {
    if (! $request->boolean('privacy')) {
        $validator->errors()->add('privacy', __('privacy::privacy.consent_required'));
    }
});

// Факт согласия — доказательство: версия текста, IP и время
Registry::onRegisterSave(static function (User $user, Request $request): void {
    PrivacyConsent::query()->updateOrCreate(['user_id' => $user->id], [
        'text_id'    => PrivacyText::current()->id,
        'ip'         => (string) $request->ip(),
        'created_at' => now(),
    ]);
});

Registry::onDeleteUser(static function (User $user): void {
    PrivacyConsent::query()->whereKey($user->id)->delete();
});

// Плитка в админке владельца: текст согласия правит только он
Hook::add('adminBlockBoss', static fn () => '<div class="col">
    <a href="' . route('privacy.admin.index') . '" class="app-tile">
        <div class="app-tile-icon" style="background:#6f42c1"><i class="fas fa-user-shield"></i></div>
        <div class="app-tile-label">' . __('privacy::privacy.admin_title') . '</div>
    </a>
</div>');
