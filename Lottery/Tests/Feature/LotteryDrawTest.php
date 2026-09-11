<?php

namespace Modules\Lottery\Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Modules\Lottery\Console\LotteryDraw;
use Modules\Lottery\Models\Lottery;
use Modules\Lottery\Services\LotteryService;
use Tests\ModuleTestCase;

class LotteryDrawTest extends ModuleTestCase
{
    protected string $moduleName = 'Lottery';

    private LotteryService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->overrideSetting('bonusmoney', 0);

        $this->service = $this->app->make(LotteryService::class);

        // ModuleTestCase поднимает вьюхи, хуки и маршруты, но не консольные команды модуля
        $this->app->make(Kernel::class)->registerCommand(new LotteryDraw());

        Lottery::query()->delete();
    }

    public function testDrawOpensTodayTirage(): void
    {
        $this->assertTrue($this->service->draw());

        $lottery = Lottery::query()->first();

        $this->assertSame(now()->format('Y-m-d'), $lottery->day);
        $this->assertSame((int) Lottery::getConfig('jackpot'), $lottery->amount);
    }

    public function testTodayNumberIsHidden(): void
    {
        // Номер тянется в момент розыгрыша: пока тираж идёт, его нет и в базе
        $this->service->draw();

        $this->assertNull(Lottery::query()->first()->number);
    }

    public function testSecondDrawSameDayDoesNothing(): void
    {
        $this->service->draw();

        $this->assertFalse($this->service->draw(), 'Тираж создан дважды за день');
        $this->assertDatabaseCount('lottery', 1);
    }

    public function testWinnerTakesWholeBank(): void
    {
        $user = User::factory()->create(['money' => 0]);
        $yesterday = $this->yesterday(5000);

        // Билет на каждый номер диапазона: победитель найдётся при любом розыгрыше
        [$min, $max] = Lottery::getConfig('numberRange');
        $yesterday->lotteryUsers()->create(['user_id' => $user->id, 'number' => $min]);

        for ($number = $min; $number <= $max; $number++) {
            $extra = User::factory()->create(['money' => 0]);
            $yesterday->lotteryUsers()->create(['user_id' => $extra->id, 'number' => $number]);
        }

        $this->service->draw();

        $drawn = $yesterday->fresh();
        $winners = $drawn->lotteryUsers()->where('number', $drawn->number)->get();
        $money = intdiv(5000, $winners->count());

        $this->assertNotNull($drawn->number);

        foreach ($winners as $winner) {
            $this->assertSame($money, $winner->user->fresh()->money, 'Победителю досталась не его доля');
        }
    }

    public function testBankIsSharedBetweenWinners(): void
    {
        $yesterday = $this->yesterday(1000);
        [$min] = Lottery::getConfig('numberRange');

        $first = User::factory()->create(['money' => 0]);
        $second = User::factory()->create(['money' => 0]);

        // Оба ставят на один номер: выпадет он — банк делится пополам
        $yesterday->lotteryUsers()->create(['user_id' => $first->id, 'number' => $min]);
        $yesterday->lotteryUsers()->create(['user_id' => $second->id, 'number' => $min]);

        $this->service->draw();

        if ($yesterday->fresh()->number !== $min) {
            $this->assertSame(0, $first->fresh()->money);
            $this->assertSame(0, $second->fresh()->money);

            return;
        }

        $this->assertSame(500, $first->fresh()->money);
        $this->assertSame(500, $second->fresh()->money);
    }

    public function testJackpotRollsOverWithoutWinners(): void
    {
        // Без билетов победителя нет, банк переходит в новый тираж целиком
        $this->yesterday(7777);

        $this->service->draw();

        $today = Lottery::query()->where('day', now()->format('Y-m-d'))->first();

        $this->assertSame(7777, $today->amount);
    }

    public function testFreshJackpotAfterWin(): void
    {
        $yesterday = $this->yesterday(3000);
        [$min, $max] = Lottery::getConfig('numberRange');

        for ($number = $min; $number <= $max; $number++) {
            $user = User::factory()->create(['money' => 0]);
            $yesterday->lotteryUsers()->create(['user_id' => $user->id, 'number' => $number]);
        }

        $this->service->draw();

        $today = Lottery::query()->where('day', now()->format('Y-m-d'))->first();

        $this->assertSame((int) Lottery::getConfig('jackpot'), $today->amount, 'Банк не обнулился после выигрыша');
    }

    public function testCommandDrawsTirage(): void
    {
        $this->artisan('lottery:draw')->assertSuccessful();

        $this->assertDatabaseHas('lottery', ['day' => now()->format('Y-m-d')]);
    }

    public function testPageDrawsWithoutScheduler(): void
    {
        // Планировщик включен не у всех: заход на страницу обязан разыграть тираж
        $this->yesterday(100);

        $this->actingAs(User::factory()->create())->get('/lottery')->assertOk();

        $this->assertDatabaseHas('lottery', ['day' => now()->format('Y-m-d')]);
    }

    /**
     * Вчерашний тираж с заданным банком
     */
    private function yesterday(int $amount): Lottery
    {
        return Lottery::query()->create([
            'day'    => now()->subDay()->format('Y-m-d'),
            'amount' => $amount,
            'number' => null,
        ]);
    }
}
