<?php

namespace Modules\Delivery\Tests\Feature;

use App\Models\User;
use Tests\ModuleTestCase;

class DeliverySmokeTest extends ModuleTestCase
{
    protected string $moduleName = 'Delivery';

    private User $boss;

    protected function setUp(): void
    {
        parent::setUp();

        $this->overrideSetting('comment_text_min', 5);
        $this->overrideSetting('comment_text_max', 1000);

        $this->boss = User::factory()->create(['level' => User::BOSS]);
    }

    public function testIndexIsOpenForBoss(): void
    {
        $this->actingAs($this->boss)->get('/admin/delivery')->assertOk();
    }

    public function testAdminIsRejected(): void
    {
        $admin = User::factory()->create(['level' => User::ADMIN]);

        $this->actingAs($admin)->get('/admin/delivery')->assertForbidden();
    }

    public function testMessageIsSentToAdmins(): void
    {
        $admin = User::factory()->create(['level' => User::ADMIN]);

        $this->actingAs($this->boss)
            ->post('/admin/delivery', ['msg' => 'Сообщение администрации', 'type' => 3])
            ->assertRedirect();

        $this->assertSame(1, $admin->fresh()->getCountMessages());

        // Отправителю рассылка не приходит
        $this->assertSame(0, $this->boss->fresh()->getCountMessages());
    }

    public function testShortMessageIsRejected(): void
    {
        User::factory()->create(['level' => User::ADMIN]);

        $this->actingAs($this->boss)
            ->post('/admin/delivery', ['msg' => 'Ок', 'type' => 3])
            ->assertSessionHasErrors('msg');
    }

    public function testUnknownRecipientTypeIsRejected(): void
    {
        $this->actingAs($this->boss)
            ->post('/admin/delivery', ['msg' => 'Сообщение администрации', 'type' => 9])
            ->assertSessionHasErrors();
    }
}
