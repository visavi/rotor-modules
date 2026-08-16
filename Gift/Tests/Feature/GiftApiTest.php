<?php

namespace Modules\Gift\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Str;
use Modules\Gift\Models\Gift;
use Modules\Gift\Models\GiftsUser;
use Tests\ModuleTestCase;

class GiftApiTest extends ModuleTestCase
{
    protected string $moduleName = 'Gift';

    private User $user;

    private User $recipient;

    private Gift $gift;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['apikey' => Str::random(32), 'money' => 500]);
        $this->recipient = User::factory()->create();

        $this->gift = Gift::query()->create([
            'name'       => 'Букет',
            'path'       => '/uploads/gifts/flowers.png',
            'price'      => 100,
            'created_at' => now(),
        ]);
    }

    public function testCatalogIsOpenForGuests(): void
    {
        // Каталог наполняется через админку, в базе уже могут быть свои подарки
        Gift::query()->whereKeyNot($this->gift->id)->delete();

        $response = $this->getJson('/api/gifts')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Букет')
            ->assertJsonPath('data.0.price', 100);

        $this->assertSame(url('/uploads/gifts/flowers.png'), $response->json('data.0.url'));
    }

    public function testSendRequiresToken(): void
    {
        $this->postJson('/api/gifts/' . $this->gift->id . '/send', ['user' => $this->recipient->login])
            ->assertStatus(400);
    }

    public function testGiftIsSent(): void
    {
        $this->postJson('/api/gifts/' . $this->gift->id . '/send', [
            'user' => $this->recipient->login,
            'text' => 'С праздником!',
        ], $this->headers())
            ->assertStatus(201)
            ->assertJsonPath('money', 400);

        $this->assertDatabaseHas('gifts_users', [
            'gift_id'      => $this->gift->id,
            'user_id'      => $this->recipient->id,
            'send_user_id' => $this->user->id,
        ]);

        // Получателю приходит уведомление в приват
        $this->assertSame(1, $this->recipient->fresh()->getCountMessages());
    }

    public function testGiftNeedsMoney(): void
    {
        $this->user->update(['money' => 10]);

        $this->postJson('/api/gifts/' . $this->gift->id . '/send', [
            'user' => $this->recipient->login,
        ], $this->headers())->assertStatus(422);

        $this->assertDatabaseCount('gifts_users', 0);
    }

    public function testUnknownGiftIsNotFound(): void
    {
        $this->postJson('/api/gifts/999999/send', ['user' => $this->recipient->login], $this->headers())
            ->assertStatus(404);
    }

    public function testUnknownRecipientIsNotFound(): void
    {
        $this->postJson('/api/gifts/' . $this->gift->id . '/send', ['user' => 'nobody'], $this->headers())
            ->assertStatus(404);
    }

    public function testUserGiftsAreListed(): void
    {
        GiftsUser::query()->create([
            'gift_id'      => $this->gift->id,
            'user_id'      => $this->recipient->id,
            'send_user_id' => $this->user->id,
            'text'         => 'С праздником!',
            'created_at'   => now(),
            'deleted_at'   => now()->addDays(30),
        ]);

        $this->getJson('/api/gifts/' . $this->recipient->login)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Букет')
            ->assertJsonPath('data.0.sender.login', $this->user->login)
            ->assertJsonPath('user.login', $this->recipient->login);
    }

    public function testExpiredGiftIsHidden(): void
    {
        GiftsUser::query()->create([
            'gift_id'      => $this->gift->id,
            'user_id'      => $this->recipient->id,
            'send_user_id' => $this->user->id,
            'text'         => 'Старый подарок',
            'created_at'   => now()->subYears(2),
            'deleted_at'   => now()->subDay(),
        ]);

        $this->getJson('/api/gifts/' . $this->recipient->login)
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    private function headers(): array
    {
        return ['Authorization' => 'Bearer ' . $this->user->apikey];
    }
}
