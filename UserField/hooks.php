<?php

use App\Classes\Hook;
use App\Classes\Registry;
use App\Classes\Validator;
use App\Models\User;
use Illuminate\Http\Request;
use Modules\UserField\Models\UserData;
use Modules\UserField\Models\UserField;

// Валидация полей при сохранении профиля (в админке обязательность не проверяется)
Registry::onProfileValidate(static function (User $user, Request $request, Validator $validator, bool $strict): void {
    $fields = UserField::query()->orderBy('sort')->get();

    foreach ($fields as $field) {
        $validator->length(
            $request->input('field' . $field->id),
            $field->min,
            $field->max,
            ['field' . $field->id => __('validator.text')],
            $strict && $field->required
        );
    }
});

// Сохранение значений полей после успешной валидации
Registry::onProfileSave(static function (User $user, Request $request): void {
    $fields = UserField::query()->get();

    foreach ($fields as $field) {
        UserData::query()->updateOrCreate([
            'user_id'  => $user->id,
            'field_id' => $field->id,
        ], [
            'value' => $field->sanitizeValue($request->input('field' . $field->id)),
        ]);
    }
});

// Удаление данных полей при удалении пользователя
Registry::onDeleteUser(static function (User $user): void {
    UserData::query()->where('user_id', $user->id)->delete();
});

// Заполненные поля в анкете пользователя
Hook::add('userFields', static function (User $user): ?string {
    $fields = UserField::query()->withUserData($user->id)->whereNotNull('user_data.value')->get();

    if ($fields->isEmpty()) {
        return null;
    }

    return view('user_field::_fields_view', compact('fields'))->render();
});

// Поля в форме редактирования профиля
Hook::add('profileFields', static function (User $user): ?string {
    $fields = UserField::query()->withUserData($user->id)->get();

    if ($fields->isEmpty()) {
        return null;
    }

    return view('user_field::_fields_form', compact('fields'))->render();
});

// Поля во вкладке редактирования пользователя в админке
Hook::add('adminUserFields', static function (User $user): string {
    $fields = UserField::query()->withUserData($user->id)->get();

    if ($fields->isEmpty()) {
        return '<div class="alert alert-warning">' . __('user_field::user_fields.empty_fields') . '</div>';
    }

    return view('user_field::_fields_form', compact('fields'))->render();
});

// Плитка в админке (блок владельца)
Hook::add('adminBlockBoss', static fn () => '<div class="col">
    <a href="/admin/user-fields" class="app-tile">
        <div class="app-tile-icon" style="background:#fd7e14"><i class="fas fa-user-edit"></i></div>
        <div class="app-tile-label">' . __('user_field::user_fields.title') . '</div>
    </a>
</div>');
