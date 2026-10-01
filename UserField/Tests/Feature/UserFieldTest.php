<?php

namespace Modules\UserField\Tests\Feature;

use App\Models\User;
use Modules\UserField\Models\UserData;
use Modules\UserField\Models\UserField;
use Tests\ModuleTestCase;

class UserFieldTest extends ModuleTestCase
{
    protected string $moduleName = 'UserField';

    private User $user;

    private UserField $field;

    protected function setUp(): void
    {
        parent::setUp();

        // Поля создаёт владелец сайта, в базе могут быть свои
        UserField::query()->delete();

        $this->user = User::factory()->create();

        $this->field = UserField::query()->create([
            'sort'     => 1,
            'type'     => UserField::INPUT,
            'name'     => 'Любимая игра',
            'min'      => 3,
            'max'      => 20,
            'required' => false,
        ]);
    }

    public function testFieldIsSavedWithProfile(): void
    {
        $this->actingAs($this->user)
            ->post('/profile', $this->profile(['field' . $this->field->id => 'Героев меча и магии']))
            ->assertRedirect('profile');

        $this->assertDatabaseHas('user_data', [
            'user_id'  => $this->user->id,
            'field_id' => $this->field->id,
            'value'    => 'Героев меча и магии',
        ]);
    }

    public function testValueIsUpdatedNotDuplicated(): void
    {
        $this->actingAs($this->user)
            ->post('/profile', $this->profile(['field' . $this->field->id => 'Первое']));

        $this->actingAs($this->user)
            ->post('/profile', $this->profile(['field' . $this->field->id => 'Второе']));

        $this->assertDatabaseCount('user_data', 1);
        $this->assertSame('Второе', UserData::query()->value('value'));
    }

    public function testShortValueIsRejected(): void
    {
        $this->actingAs($this->user)
            ->post('/profile', $this->profile(['field' . $this->field->id => 'Ок']))
            ->assertSessionHasErrors('field' . $this->field->id);

        $this->assertDatabaseCount('user_data', 0);
    }

    public function testRequiredFieldCannotBeEmpty(): void
    {
        $this->field->update(['required' => true]);

        $this->actingAs($this->user)
            ->post('/profile', $this->profile(['field' . $this->field->id => '']))
            ->assertSessionHasErrors('field' . $this->field->id);
    }

    public function testOptionalFieldMayBeEmpty(): void
    {
        $this->actingAs($this->user)
            ->post('/profile', $this->profile(['field' . $this->field->id => '']))
            ->assertRedirect('profile');

        $this->assertSame(null, UserData::query()->value('value'));
    }

    public function testTextareaValueIsSanitized(): void
    {
        $field = UserField::query()->create([
            'sort'     => 2,
            'type'     => UserField::TEXTAREA,
            'name'     => 'О себе',
            'min'      => 0,
            'max'      => 1000,
            'required' => false,
        ]);

        $this->actingAs($this->user)
            ->post('/profile', $this->profile([
                'field' . $this->field->id => 'Героев меча и магии',
                'field' . $field->id       => '<strong>жирный</strong><script>alert(1)</script>',
            ]))
            ->assertRedirect('profile');

        $value = UserData::query()->where('field_id', $field->id)->value('value');

        $this->assertStringContainsString('<strong>жирный</strong>', $value);
        $this->assertStringNotContainsString('<script>', $value);
    }

    public function testFieldsAreShownInProfileForm(): void
    {
        $this->actingAs($this->user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('Любимая игра');
    }

    public function testFilledFieldIsShownInUserCard(): void
    {
        UserData::query()->create([
            'user_id'  => $this->user->id,
            'field_id' => $this->field->id,
            'value'    => 'Героев меча и магии',
        ]);

        $this->actingAs($this->user)
            ->get('/users/' . $this->user->login)
            ->assertOk()
            ->assertSee('Героев меча и магии');
    }

    public function testDataIsRemovedWithUser(): void
    {
        UserData::query()->create([
            'user_id'  => $this->user->id,
            'field_id' => $this->field->id,
            'value'    => 'Героев меча и магии',
        ]);

        // Чистит хук onDeleteUser
        $this->user->delete();

        $this->assertDatabaseCount('user_data', 0);
    }

    /**
     * Порядок полей меняется перетаскиванием в админке
     */
    public function testAdminSortsFields(): void
    {
        $boss = User::factory()->create(['level' => User::BOSS]);

        $second = UserField::query()->create([
            'sort'     => 2,
            'type'     => UserField::INPUT,
            'name'     => 'Город',
            'min'      => 0,
            'max'      => 50,
            'required' => false,
        ]);

        $this->actingAs($boss)
            ->post('/admin/user-fields/sort', ['order' => $second->id . ',' . $this->field->id])
            ->assertRedirect('admin/user-fields');

        $this->assertSame(1, $second->fresh()->sort);
        $this->assertSame(2, $this->field->fresh()->sort);
    }

    /**
     * Положения в форме нет: новое поле встаёт последним
     */
    public function testCreatedFieldGoesLast(): void
    {
        $boss = User::factory()->create(['level' => User::BOSS]);

        $this->actingAs($boss)
            ->post('/admin/user-fields', [
                'type'     => UserField::INPUT,
                'name'     => 'Город',
                'min'      => 0,
                'max'      => 50,
                'required' => 0,
            ])
            ->assertRedirect('admin/user-fields');

        $this->assertSame(2, UserField::query()->where('name', 'Город')->value('sort'));
    }

    public function testPlaceholderAndHintAreShownInForm(): void
    {
        $this->field->update(['placeholder' => 'Например, Цивилизация', 'hint' => 'Одна игра, без списка']);

        $this->actingAs($this->user)
            ->get('/profile')
            ->assertOk()
            ->assertSee('placeholder="Например, Цивилизация"', false)
            ->assertSee('Одна игра, без списка');
    }

    public function testUrlFieldValidatesAndRendersLink(): void
    {
        $field = $this->createField(UserField::URL, ['max' => 100]);

        $this->actingAs($this->user)
            ->post('/profile', $this->profile(['field' . $field->id => 'не ссылка']))
            ->assertSessionHasErrors('field' . $field->id);

        $this->actingAs($this->user)
            ->post('/profile', $this->profile(['field' . $field->id => 'https://example.com']))
            ->assertRedirect('profile');

        $this->get('/users/' . $this->user->login)
            ->assertSee('<a href="https://example.com" target="_blank" rel="nofollow noopener">', false);
    }

    /**
     * У поля сменили тип на «Ссылка», а старое значение осталось текстом — ссылкой оно не становится
     */
    public function testUrlFieldDoesNotLinkArbitraryText(): void
    {
        $field = $this->createField(UserField::URL, ['max' => 100]);
        UserData::query()->create(['user_id' => $this->user->id, 'field_id' => $field->id, 'value' => 'javascript:alert(1)']);

        $this->actingAs($this->user)
            ->get('/users/' . $this->user->login)
            ->assertOk()
            ->assertDontSee('href="javascript:', false);
    }

    public function testTelFieldRendersTelLink(): void
    {
        $field = $this->createField(UserField::TEL);

        $this->actingAs($this->user)
            ->post('/profile', $this->profile(['field' . $field->id => '+7 (900) 123-45-67']))
            ->assertRedirect('profile');

        $this->get('/users/' . $this->user->login)
            ->assertSee('<a href="tel:+79001234567">', false);
    }

    public function testNumberFieldChecksRange(): void
    {
        $field = $this->createField(UserField::NUMBER, ['min' => 1, 'max' => 100]);

        $this->actingAs($this->user)
            ->post('/profile', $this->profile(['field' . $field->id => '150']))
            ->assertSessionHasErrors('field' . $field->id);

        $this->actingAs($this->user)
            ->post('/profile', $this->profile(['field' . $field->id => 'abc']))
            ->assertSessionHasErrors('field' . $field->id);

        $this->actingAs($this->user)
            ->post('/profile', $this->profile(['field' . $field->id => '42']))
            ->assertRedirect('profile');
    }

    public function testDateFieldValidatesAndRendersLocalFormat(): void
    {
        $field = $this->createField(UserField::DATE);

        $this->actingAs($this->user)
            ->post('/profile', $this->profile(['field' . $field->id => '2026-02-30']))
            ->assertSessionHasErrors('field' . $field->id);

        $this->actingAs($this->user)
            ->post('/profile', $this->profile(['field' . $field->id => '2026-02-14']))
            ->assertRedirect('profile');

        $this->get('/users/' . $this->user->login)->assertSee('14.02.2026');
    }

    public function testSelectFieldAcceptsOnlyOptions(): void
    {
        $field = $this->createField(UserField::SELECT, ['options' => "Москва\r\nКазань\n\nСочи ", 'required' => true]);

        $this->actingAs($this->user)
            ->get('/profile')
            ->assertSee('<option value="Казань">', false)
            ->assertSee('<option value="Сочи">', false);

        $this->actingAs($this->user)
            ->post('/profile', $this->profile(['field' . $field->id => 'Урюпинск']))
            ->assertSessionHasErrors('field' . $field->id);

        $this->actingAs($this->user)
            ->post('/profile', $this->profile(['field' . $field->id => '']))
            ->assertSessionHasErrors('field' . $field->id);

        $this->actingAs($this->user)
            ->post('/profile', $this->profile(['field' . $field->id => 'Казань']))
            ->assertRedirect('profile');
    }

    public function testRequiredSelectInvitationCannotBeChosen(): void
    {
        $this->createField(UserField::SELECT, ['options' => "Москва\nКазань", 'required' => true]);

        $this->actingAs($this->user)
            ->get('/profile')
            ->assertSee('<option value="" disabled selected>' . __('user_field::user_fields.select_choose') . '</option>', false);
    }

    public function testOptionalSelectCanBeCleared(): void
    {
        $field = $this->createField(UserField::SELECT, ['options' => "Москва\nКазань"]);
        UserData::query()->create(['user_id' => $this->user->id, 'field_id' => $field->id, 'value' => 'Казань']);

        $this->actingAs($this->user)
            ->get('/profile')
            ->assertSee('<option value="">' . __('user_field::user_fields.select_none') . '</option>', false);

        $this->actingAs($this->user)
            ->post('/profile', $this->profile(['field' . $field->id => '']))
            ->assertRedirect('profile');

        $this->assertNull(UserData::query()->where('field_id', $field->id)->value('value'));
    }

    public function testRadioFieldAcceptsOnlyOptions(): void
    {
        $field = $this->createField(UserField::RADIO, ['options' => "Новичок\nПрофи"]);

        // У необязательного поля есть пустой вариант: выбранную радиокнопку иначе не снять
        $this->actingAs($this->user)
            ->get('/profile')
            ->assertSee('value="" checked', false)
            ->assertSee('value="Профи"', false);

        $this->actingAs($this->user)
            ->post('/profile', $this->profile(['field' . $field->id => 'Гуру']))
            ->assertSessionHasErrors('field' . $field->id);

        $this->actingAs($this->user)
            ->post('/profile', $this->profile(['field' . $field->id => 'Профи']))
            ->assertRedirect('profile');

        $this->get('/users/' . $this->user->login)->assertSee('Профи');
    }

    public function testRequiredRadioHasNoEmptyOption(): void
    {
        $this->createField(UserField::RADIO, ['options' => "Новичок\nПрофи", 'required' => true]);

        $this->actingAs($this->user)
            ->get('/profile')
            ->assertDontSee(__('user_field::user_fields.select_none'));
    }

    public function testCheckboxIsShownOnlyWhenChecked(): void
    {
        $field = $this->createField(UserField::CHECKBOX, ['name' => 'Готов помогать с модерацией']);

        $this->actingAs($this->user)
            ->post('/profile', $this->profile(['field' . $field->id => '1']))
            ->assertRedirect('profile');

        $this->get('/users/' . $this->user->login)
            ->assertSee('Готов помогать с модерацией')
            ->assertSee(__('main.yes'));

        // Выключенный переключатель приходит пустым из скрытого поля формы
        $this->actingAs($this->user)
            ->post('/profile', $this->profile(['field' . $field->id => '']))
            ->assertRedirect('profile');

        $this->assertNull(UserData::query()->where('field_id', $field->id)->value('value'));

        $this->get('/users/' . $this->user->login)
            ->assertDontSee('Готов помогать с модерацией');
    }

    public function testRequiredCheckboxMustBeChecked(): void
    {
        $field = $this->createField(UserField::CHECKBOX, ['name' => 'Согласен с правилами', 'required' => true]);

        $this->actingAs($this->user)
            ->post('/profile', $this->profile(['field' . $field->id => '']))
            ->assertSessionHasErrors('field' . $field->id);
    }

    /**
     * Форма прячет и не отправляет мин./макс. у типов без ограничений — сервер ставит нули
     */
    public function testAdminCreatesCheckboxWithoutLimits(): void
    {
        $boss = User::factory()->create(['level' => User::BOSS]);

        $this->actingAs($boss)
            ->post('/admin/user-fields', [
                'type'     => UserField::CHECKBOX,
                'name'     => 'Согласен с правилами',
                'required' => 1,
            ])
            ->assertRedirect('admin/user-fields');

        $this->assertDatabaseHas('user_fields', ['name' => 'Согласен с правилами', 'min' => 0, 'max' => 0]);
    }

    public function testAdminTextFieldStillRequiresLimits(): void
    {
        $boss = User::factory()->create(['level' => User::BOSS]);

        $this->actingAs($boss)
            ->post('/admin/user-fields', ['type' => UserField::INPUT, 'name' => 'Город', 'required' => 0])
            ->assertSessionHasErrors(['min', 'max']);
    }

    public function testAdminFormShowsOnlySettingsOfType(): void
    {
        $boss = User::factory()->create(['level' => User::BOSS]);
        $select = $this->createField(UserField::SELECT, ['options' => "Москва\nКазань"]);

        // У списка есть варианты, а мин./макс. скрыты и не отправляются
        $this->actingAs($boss)
            ->get('/admin/user-fields/' . $select->id . '/edit')
            ->assertOk()
            ->assertSee('id="options" rows="4">', false)
            ->assertSee('id="min" value="0" disabled required', false);
    }

    public function testTypeChangeDeletesAnswers(): void
    {
        $boss = User::factory()->create(['level' => User::BOSS]);
        $field = $this->createField(UserField::SELECT, ['options' => "Москва\nКазань"]);
        UserData::query()->create(['user_id' => $this->user->id, 'field_id' => $field->id, 'value' => 'Казань']);

        // Форма предупреждает заранее
        $this->actingAs($boss)
            ->get('/admin/user-fields/' . $field->id . '/edit')
            ->assertSee(__('user_field::user_fields.type_change_warning', ['count' => 1]));

        $this->actingAs($boss)
            ->put('/admin/user-fields/' . $field->id, ['type' => UserField::CHECKBOX, 'name' => $field->name, 'required' => 0])
            ->assertRedirect('admin/user-fields');

        $this->assertDatabaseMissing('user_data', ['field_id' => $field->id]);
    }

    public function testSameTypeKeepsAnswers(): void
    {
        $boss = User::factory()->create(['level' => User::BOSS]);
        UserData::query()->create(['user_id' => $this->user->id, 'field_id' => $this->field->id, 'value' => 'Героев меча и магии']);

        $this->actingAs($boss)
            ->put('/admin/user-fields/' . $this->field->id, ['type' => UserField::INPUT, 'name' => 'Любимая игра', 'min' => 3, 'max' => 30, 'required' => 0])
            ->assertRedirect('admin/user-fields');

        $this->assertDatabaseHas('user_data', ['field_id' => $this->field->id, 'value' => 'Героев меча и магии']);
    }

    public function testAdminCannotCreateRadioWithOneOption(): void
    {
        $boss = User::factory()->create(['level' => User::BOSS]);

        $this->actingAs($boss)
            ->post('/admin/user-fields', ['type' => UserField::RADIO, 'name' => 'Опыт', 'options' => "Профи\n\n ", 'required' => 0])
            ->assertSessionHasErrors('options');
    }

    public function testFormSendsEveryField(): void
    {
        $this->actingAs($this->user)
            ->get('/profile')
            ->assertSee('<input type="hidden" name="field' . $this->field->id . '" value="">', false);
    }

    /**
     * Клиент API о полях модуля не знает: сохранение профиля их не затирает и обязательные не блокируют
     */
    public function testApiProfileKeepsFields(): void
    {
        $this->user->update(['apikey' => 'api-key-user-field-test']);
        $this->createField(UserField::CHECKBOX, ['name' => 'Согласен с правилами', 'required' => true]);
        UserData::query()->create(['user_id' => $this->user->id, 'field_id' => $this->field->id, 'value' => 'Героев меча и магии']);

        $this->patchJson('/api/user', ['name' => 'Тестовый', 'gender' => User::MALE], ['Authorization' => 'Bearer api-key-user-field-test'])
            ->assertOk();

        $this->assertSame('Героев меча и магии', UserData::query()->where('field_id', $this->field->id)->value('value'));
    }

    public function testAdminCannotCreateSelectWithoutOptions(): void
    {
        $boss = User::factory()->create(['level' => User::BOSS]);

        $this->actingAs($boss)
            ->post('/admin/user-fields', [
                'type'     => UserField::SELECT,
                'name'     => 'Город',
                'min'      => 0,
                'max'      => 0,
                'required' => 0,
            ])
            ->assertSessionHasErrors('options');

        $this->actingAs($boss)
            ->post('/admin/user-fields', [
                'type'     => UserField::SELECT,
                'name'     => 'Город',
                'options'  => "Москва\nКазань",
                'min'      => 0,
                'max'      => 0,
                'required' => 0,
            ])
            ->assertRedirect('admin/user-fields');

        $this->assertDatabaseHas('user_fields', ['name' => 'Город', 'type' => UserField::SELECT]);
    }

    /**
     * Профиль сохраняется целиком, поля модуля идут вместе с полями ядра
     */
    private function profile(array $fields): array
    {
        return [
            'name'   => 'Тестовый',
            'gender' => User::MALE,
            'info'   => 'Немного о себе',
            ...$fields,
        ];
    }

    private function createField(string $type, array $attributes = []): UserField
    {
        return UserField::query()->create($attributes + [
            'sort'     => 2,
            'type'     => $type,
            'name'     => 'Поле ' . $type,
            'min'      => 0,
            'max'      => 50,
            'required' => false,
        ]);
    }
}
