<?php

namespace Modules\Lottery\Tests\Feature;

use App\Models\User;
use Tests\ModuleTestCase;

class LotterySettingsTest extends ModuleTestCase
{
    protected string $moduleName = 'Lottery';

    private User $boss;

    protected function setUp(): void
    {
        parent::setUp();

        $this->overrideSetting('lottery_jackpot', 1000000);
        $this->overrideSetting('lottery_ticket_price', 50);
        $this->overrideSetting('lottery_min', 1);
        $this->overrideSetting('lottery_max', 100);

        $this->boss = User::factory()->boss()->create();
    }

    private function save(array $sets): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->boss)->post(route('lottery.settings.update'), [
            'sets' => $sets + [
                'lottery_jackpot'      => 1000000,
                'lottery_ticket_price' => 50,
                'lottery_min'          => 1,
                'lottery_max'          => 100,
            ],
        ]);
    }

    public function testSettingsPageIsOpen(): void
    {
        $this->actingAs($this->boss)
            ->get(route('lottery.settings'))
            ->assertOk()
            ->assertSee('name="sets[lottery_max]"', false);
    }

    public function testRangeIsSaved(): void
    {
        $this->save(['lottery_min' => 5, 'lottery_max' => 50])
            ->assertRedirect(route('lottery.settings'))
            ->assertSessionHasNoErrors();

        $this->assertSame(5, setting('lottery_min'));
        $this->assertSame(50, setting('lottery_max'));
    }

    public function testInvertedRangeIsRejected(): void
    {
        // Минимум больше максимума уронил бы random_int в розыгрыше
        $this->save(['lottery_min' => 100, 'lottery_max' => 10])
            ->assertSessionHasErrors('sets[lottery_max]');

        $this->assertSame(100, setting('lottery_max'));
    }

    public function testNumberOutOfColumnIsRejected(): void
    {
        // Номер хранится в smallInteger
        $this->save(['lottery_max' => 40000])
            ->assertSessionHasErrors('sets[lottery_max]');
    }
}
