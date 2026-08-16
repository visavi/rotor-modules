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
}
