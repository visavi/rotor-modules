<?php

namespace Modules\Notebook\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Str;
use Modules\Notebook\Models\Notebook;
use Tests\ModuleTestCase;

class NotebookTest extends ModuleTestCase
{
    protected string $moduleName = 'Notebook';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function testEditFormOpens(): void
    {
        Notebook::factory()->create(['user_id' => $this->user->id, 'text' => 'Список дел']);

        $this->actingAs($this->user)
            ->get('/notebooks/edit')
            ->assertOk()
            ->assertSee('Список дел');
    }

    public function testNoteIsSaved(): void
    {
        $this->actingAs($this->user)
            ->post('/notebooks/edit', ['text' => 'Не забыть купить хлеб'])
            ->assertRedirect(route('notebooks.index'));

        $this->assertSame('Не забыть купить хлеб', Notebook::query()->where('user_id', $this->user->id)->value('text'));
    }

    public function testEmptyNoteIsSaved(): void
    {
        Notebook::factory()->create(['user_id' => $this->user->id, 'text' => 'Старая заметка']);

        $this->actingAs($this->user)
            ->post('/notebooks/edit', ['text' => ''])
            ->assertRedirect(route('notebooks.index'));

        $this->assertSame('', Notebook::query()->where('user_id', $this->user->id)->value('text'));
    }

    public function testTooLongTextShowsError(): void
    {
        $this->actingAs($this->user)
            ->from('/notebooks/edit')
            ->post('/notebooks/edit', ['text' => Str::repeat('a', 10001)])
            ->assertRedirect('/notebooks/edit')
            ->assertSessionHasErrors('text');

        $this->assertFalse(Notebook::query()->where('user_id', $this->user->id)->exists());
    }

    public function testMarkupIsNotCountedInLength(): void
    {
        $this->actingAs($this->user)
            ->post('/notebooks/edit', ['text' => '<p>' . Str::repeat('a', 10000) . '</p>'])
            ->assertRedirect(route('notebooks.index'))
            ->assertSessionHasNoErrors();
    }

    public function testGuestCannotEdit(): void
    {
        $this->get('/notebooks/edit')->assertForbidden();
        $this->post('/notebooks/edit', ['text' => 'Гость'])->assertForbidden();
    }
}
