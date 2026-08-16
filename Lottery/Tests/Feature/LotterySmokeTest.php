<?php

namespace Modules\Lottery\Tests\Feature;

use App\Models\User;
use Modules\Lottery\Models\Lottery;
use Tests\ModuleTestCase;

class LotterySmokeTest extends ModuleTestCase
{
    protected string $moduleName = 'Lottery';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Ежедневный бонус ядра начисляется прямо в запросе и сбивал бы счёт денег
        $this->overrideSetting('bonusmoney', 0);

        $this->user = User::factory()->create(['money' => 1000]);
    }

    public function testIndexIsOpen(): void
    {
        $this->actingAs($this->user)->get('/lottery')->assertOk();
    }

    public function testTicketIsBought(): void
    {
        $lottery = $this->lottery();

        $this->actingAs($this->user)
            ->post('/lottery/buy', ['number' => 42])
            ->assertRedirect('lottery');

        $this->assertDatabaseHas('lottery_users', [
            'lottery_id' => $lottery->id,
            'user_id'    => $this->user->id,
            'number'     => 42,
        ]);

        // Билет стоит ticketPrice из module.php
        $this->assertSame(950, $this->user->fresh()->money);
    }

    public function testSecondTicketIsRejected(): void
    {
        $this->lottery();

        $this->actingAs($this->user)->post('/lottery/buy', ['number' => 42]);
        $this->actingAs($this->user)
            ->post('/lottery/buy', ['number' => 7])
            ->assertSessionHasErrors('number');

        $this->assertDatabaseCount('lottery_users', 1);
    }

    public function testNumberOutOfRangeIsRejected(): void
    {
        $this->lottery();

        $this->actingAs($this->user)
            ->post('/lottery/buy', ['number' => 500])
            ->assertSessionHasErrors('number');

        $this->assertDatabaseCount('lottery_users', 0);
    }

    public function testPurchaseNeedsMoney(): void
    {
        $this->lottery();
        $this->user->update(['money' => 10]);

        $this->actingAs($this->user)
            ->post('/lottery/buy', ['number' => 42])
            ->assertSessionHasErrors('number');

        $this->assertDatabaseCount('lottery_users', 0);
    }

    public function testGuestCannotBuy(): void
    {
        $this->lottery();

        $this->post('/lottery/buy', ['number' => 42])->assertForbidden();
    }

    private function lottery(): Lottery
    {
        Lottery::query()->delete();

        return Lottery::query()->create([
            'day'    => now()->format('Y-m-d'),
            'amount' => 0,
            'number' => 0,
        ]);
    }
}
