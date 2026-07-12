<?php

namespace Modules\Template\Tests\Feature;

use App\Models\User;
use Modules\Template\Models\Template;
use Tests\ModuleTestCase;

class TemplateSmokeTest extends ModuleTestCase
{
    protected string $moduleName = 'Template';

    public function testIndex(): void
    {
        $this->get(route('template.index'))->assertOk();
    }

    public function testRecordVisible(): void
    {
        $user = User::factory()->create();

        Template::query()->create([
            'user_id' => $user->id,
            'title'   => 'Test title',
            'text'    => 'Test text',
        ]);

        $this->get(route('template.index'))
            ->assertOk()
            ->assertSee('Test title');
    }

    public function testStoreRequiresAuth(): void
    {
        $this->post(route('template.store'), [
            'title' => 'Test title',
            'text'  => 'Test text',
        ])->assertRedirect();

        $this->assertDatabaseCount('templates', 0);
    }

    public function testStore(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('template.store'), [
                'title' => 'Test title',
                'text'  => 'Test text',
            ])
            ->assertRedirect(route('template.index'));

        $this->assertDatabaseHas('templates', [
            'user_id' => $user->id,
            'title'   => 'Test title',
        ]);
    }
}
