<?php

namespace Modules\Notebook\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Str;
use Modules\Notebook\Models\Notebook;
use Tests\ModuleTestCase;

class NotebookApiTest extends ModuleTestCase
{
    protected string $moduleName = 'Notebook';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['apikey' => Str::random(32)]);
    }

    public function testNotebookRequiresToken(): void
    {
        $this->getJson('/api/notebook')->assertStatus(400);
    }

    public function testEmptyNotebookIsReturned(): void
    {
        $this->getJson('/api/notebook', $this->headers())
            ->assertOk()
            ->assertJsonPath('data.text', null);
    }

    public function testNotebookIsSaved(): void
    {
        $this->patchJson('/api/notebook', ['text' => 'Не забыть купить хлеб'], $this->headers())
            ->assertOk()
            ->assertJsonPath('data.text', 'Не забыть купить хлеб');

        $this->getJson('/api/notebook', $this->headers())
            ->assertOk()
            ->assertJsonPath('data.text', 'Не забыть купить хлеб');

        $this->assertDatabaseCount('notebooks', 1);
    }

    public function testSecondSaveUpdatesSameNote(): void
    {
        $this->patchJson('/api/notebook', ['text' => 'Первая версия'], $this->headers())->assertOk();
        $this->patchJson('/api/notebook', ['text' => 'Вторая версия'], $this->headers())->assertOk();

        // Заметка одна на пользователя, вторая запись её перезаписывает
        $this->assertDatabaseCount('notebooks', 1);
        $this->assertSame('Вторая версия', Notebook::query()->where('user_id', $this->user->id)->value('text'));
    }

    public function testForeignNotebookIsNotVisible(): void
    {
        $other = User::factory()->create();

        Notebook::query()->create(['user_id' => $other->id, 'text' => 'Чужая заметка']);

        $this->getJson('/api/notebook', $this->headers())
            ->assertOk()
            ->assertJsonPath('data.text', null);
    }

    public function testTooLongTextIsRejected(): void
    {
        $this->patchJson('/api/notebook', ['text' => Str::repeat('a', 10001)], $this->headers())
            ->assertStatus(422)
            ->assertJsonValidationErrors('text');
    }

    private function headers(): array
    {
        return ['Authorization' => 'Bearer ' . $this->user->apikey];
    }
}
