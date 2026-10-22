<?php

namespace Modules\Gift\Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Modules\Gift\Models\Gift;
use Modules\Gift\Models\GiftsUser;
use Modules\Gift\Services\GiftService;
use Tests\ModuleTestCase;

class GiftTest extends ModuleTestCase
{
    protected string $moduleName = 'Gift';

    private User $user;

    private User $recipient;

    private Gift $gift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->overrideSetting('gift_per_page', 24);
        $this->overrideSetting('gift_days', 365);
        $this->overrideSetting('gift_max_users', 10);

        $this->user = User::factory()->create(['money' => 500]);
        $this->recipient = User::factory()->create();

        $this->gift = Gift::factory()->create([
            'name'       => 'Букет',
            'path'       => '/uploads/gifts/flowers.png',
            'price'      => 100,
            'created_at' => now(),
        ]);
    }

    public function testSendFormOpens(): void
    {
        $this->actingAs($this->user)
            ->get('/gifts/send/' . $this->gift->id . '?user=' . $this->recipient->login)
            ->assertOk()
            ->assertSee('value="' . $this->recipient->login . '"', false);
    }

    public function testGuestCannotSend(): void
    {
        $this->get('/gifts/send/' . $this->gift->id)->assertForbidden();
        $this->post('/gifts/send/' . $this->gift->id, ['users' => [$this->recipient->login]])->assertForbidden();
    }

    public function testGiftIsSentOnceToRepeatedLogin(): void
    {
        $this->actingAs($this->user)
            ->post('/gifts/send/' . $this->gift->id, [
                'users' => [$this->recipient->login, $this->recipient->login],
                'text'  => '<p>С праздником!</p>',
            ])
            ->assertRedirect('gifts')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('gifts_users', [
            'gift_id'      => $this->gift->id,
            'user_id'      => $this->recipient->id,
            'send_user_id' => $this->user->id,
            'text'         => '<p>С праздником!</p>',
        ]);
        $this->assertDatabaseCount('gifts_users', 1);
        $this->assertSame(400, $this->user->fresh()->money);
    }

    public function testUnknownRecipientIsShownUnderUsers(): void
    {
        $this->actingAs($this->user)
            ->post('/gifts/send/' . $this->gift->id, ['users' => [$this->recipient->login, 'nobody']])
            ->assertSessionHasErrors('users');

        $this->assertDatabaseCount('gifts_users', 0);
    }

    public function testLongTextIsRejected(): void
    {
        $this->actingAs($this->user)
            ->post('/gifts/send/' . $this->gift->id, [
                'users' => [$this->recipient->login],
                'text'  => str_repeat('а', 1001),
            ])
            ->assertSessionHasErrors('text');

        $this->assertDatabaseCount('gifts_users', 0);
    }

    public function testNotEnoughMoney(): void
    {
        $this->user->update(['money' => 150]);
        $second = User::factory()->create();

        $this->actingAs($this->user)
            ->post('/gifts/send/' . $this->gift->id, ['users' => [$this->recipient->login, $second->login]])
            ->assertSessionHasErrors('money');

        $this->assertDatabaseCount('gifts_users', 0);
        $this->assertSame(150, $this->user->fresh()->money);
    }

    public function testStaleBalanceDoesNotGoNegative(): void
    {
        // Запрос проверил баланс, а параллельный уже его потратил
        $stale = $this->user->fresh();
        $this->user->update(['money' => 50]);

        try {
            app(GiftService::class)->send($stale, $this->gift, new Collection([$this->recipient]), '');
            $this->fail('Подарок ушёл без денег');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('money', $e->errors());
        }

        $this->assertSame(50, $this->user->fresh()->money);
        $this->assertDatabaseCount('gifts_users', 0);
    }

    public function testBossSavesPrices(): void
    {
        $boss = User::factory()->create(['level' => User::BOSS]);

        $this->actingAs($boss)
            ->post('/admin/gifts', ['gifts' => [$this->gift->id => 'дорого']])
            ->assertSessionHasErrors('gifts.' . $this->gift->id);

        $this->actingAs($boss)
            ->post('/admin/gifts', ['gifts' => [$this->gift->id => 250]])
            ->assertRedirect('admin/gifts')
            ->assertSessionHas('success');

        $this->assertSame(250, (int) $this->gift->fresh()->price);
    }

    public function testBossDeletesUserGift(): void
    {
        $boss = User::factory()->create(['level' => User::BOSS]);
        $given = GiftsUser::factory()->create([
            'gift_id'      => $this->gift->id,
            'user_id'      => $this->recipient->id,
            'send_user_id' => $this->user->id,
            'text'         => '',
            'created_at'   => now(),
            'deleted_at'   => now()->addDays(30),
        ]);

        $this->actingAs($boss)
            ->delete('/admin/gifts/' . $given->id, ['user' => $this->recipient->login])
            ->assertRedirect('gifts/' . $this->recipient->login)
            ->assertSessionHas('success');

        $this->assertNull($given->fresh());

        $this->actingAs($boss)
            ->delete('/admin/gifts/' . $given->id, ['user' => $this->recipient->login])
            ->assertSessionHasErrors();
    }
}
