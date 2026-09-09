<?php

namespace Modules\Game\Tests\Feature;

use App\Models\User;
use Modules\Game\Http\Controllers\MinerController;
use Tests\ModuleTestCase;

class MinerTest extends ModuleTestCase
{
    protected string $moduleName = 'Game';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->overrideSetting('bonusmoney', 0);

        $this->user = User::factory()->create(['money' => 5000]);
    }

    public function testPageNeedsAuth(): void
    {
        $this->get('/games/miner')->assertForbidden();
    }

    public function testPageIsOpen(): void
    {
        $this->actingAs($this->user)->get('/games/miner')->assertOk();
    }

    public function testBetChargesMoneyAndLaysMines(): void
    {
        $this->actingAs($this->user)
            ->post('/games/miner/bet', ['bet' => 100, 'mines' => 3])
            ->assertRedirect('games/miner/game');

        $this->assertSame(4900, $this->user->fresh()->money);

        $miner = session('miner');

        $this->assertCount(3, $miner['field']);
        $this->assertSame([], $miner['opened']);
        $this->assertNull($miner['status']);

        foreach ($miner['field'] as $cell) {
            $this->assertGreaterThanOrEqual(0, $cell);
            $this->assertLessThan(MinerController::CELLS, $cell);
        }
    }

    public function testBetRejectsForeignMineCount(): void
    {
        $this->actingAs($this->user)
            ->post('/games/miner/bet', ['bet' => 100, 'mines' => 24])
            ->assertRedirect('games/miner');

        $this->assertSame(5000, $this->user->fresh()->money);
        $this->assertNull(session('miner'));
    }

    public function testMineEndsGameAndKeepsBet(): void
    {
        $this->actingAs($this->user)->post('/games/miner/bet', ['bet' => 100, 'mines' => 3]);

        $mine = session('miner')['field'][0];

        $this->actingAs($this->user)->post('/games/miner/go', ['cell' => $mine]);

        $this->assertSame('lost', session('miner')['status']);
        $this->assertSame(4900, $this->user->fresh()->money, 'Ставка возвращаться не должна');

        // Доигранная партия не принимает ходы
        $this->actingAs($this->user)
            ->post('/games/miner/go', ['cell' => $this->safeCell()])
            ->assertRedirect('games/miner');
    }

    public function testCashOutPaysByOpenedCells(): void
    {
        $this->actingAs($this->user)->post('/games/miner/bet', ['bet' => 100, 'mines' => 3]);

        $this->actingAs($this->user)->post('/games/miner/go', ['cell' => $this->safeCell()]);
        $this->actingAs($this->user)->post('/games/miner/cash');

        // Одна клетка при трёх минах: 0.97 / (22/25) = 1.10 → 110 монет
        $this->assertSame(5010, $this->user->fresh()->money);
        $this->assertSame('won', session('miner')['status']);
    }

    public function testCashOutNeedsOpenedCell(): void
    {
        $this->actingAs($this->user)->post('/games/miner/bet', ['bet' => 100, 'mines' => 3]);

        $this->actingAs($this->user)->post('/games/miner/cash');

        $this->assertSame(4900, $this->user->fresh()->money, 'Выплата без единого хода');
        $this->assertNull(session('miner')['status']);
    }

    public function testSameCellCountsOnce(): void
    {
        $this->actingAs($this->user)->post('/games/miner/bet', ['bet' => 100, 'mines' => 3]);

        $cell = $this->safeCell();

        $this->actingAs($this->user)->post('/games/miner/go', ['cell' => $cell]);
        $this->actingAs($this->user)->post('/games/miner/go', ['cell' => $cell]);

        $this->assertCount(1, session('miner')['opened']);
    }

    public function testCellOutsideFieldIsIgnored(): void
    {
        $this->actingAs($this->user)->post('/games/miner/bet', ['bet' => 100, 'mines' => 3]);

        $this->actingAs($this->user)
            ->post('/games/miner/go', ['cell' => MinerController::CELLS])
            ->assertRedirect('games/miner/game');

        $this->assertSame([], session('miner')['opened']);
    }

    public function testRepeatStartsSameBet(): void
    {
        $this->actingAs($this->user)->post('/games/miner/bet', ['bet' => 100, 'mines' => 5]);

        $mine = session('miner')['field'][0];
        $this->actingAs($this->user)->post('/games/miner/go', ['cell' => $mine]);

        // Повтор доигранной партии начинает новую с той же ставкой
        $this->actingAs($this->user)
            ->post('/games/miner/bet', ['bet' => 100, 'mines' => 5])
            ->assertRedirect('games/miner/game');

        $miner = session('miner');

        $this->assertNull($miner['status']);
        $this->assertSame([], $miner['opened']);
        $this->assertSame(100, $miner['bet']);
        $this->assertSame(5, $miner['mines']);
        $this->assertSame(4800, $this->user->fresh()->money);
    }

    public function testRepeatDoesNotRestartLiveGame(): void
    {
        $this->actingAs($this->user)->post('/games/miner/bet', ['bet' => 100, 'mines' => 3]);
        $this->actingAs($this->user)->post('/games/miner/go', ['cell' => $this->safeCell()]);

        $field = session('miner')['field'];

        $this->actingAs($this->user)->post('/games/miner/bet', ['bet' => 100, 'mines' => 3]);

        $this->assertSame($field, session('miner')['field'], 'Идущая партия перезапустилась');
        $this->assertCount(1, session('miner')['opened']);
        $this->assertSame(4900, $this->user->fresh()->money, 'Ставка списана дважды');
    }

    public function testPayoutsAreRounded(): void
    {
        // Со ставки в сто монет выплаты кратны пяти
        $this->user->update(['money' => 100000]);

        foreach (MinerController::MINES as $mines) {
            foreach ([1, 2, 3, 5] as $steps) {
                $payout = $this->payoutFor($mines, $steps);

                $this->assertSame(
                    0,
                    $payout % 5,
                    "Мин {$mines}, шагов {$steps}: выплата {$payout} не круглая"
                );
            }
        }
    }

    public function testNoStepIsProfitableForPlayer(): void
    {
        // Округление не должно перекрывать долю заведения ни на одном шаге
        $method = new \ReflectionMethod(MinerController::class, 'multiplier');

        foreach (MinerController::MINES as $mines) {
            $safe = MinerController::CELLS - $mines;
            $chance = 1.0;

            for ($steps = 1; $steps <= $safe; $steps++) {
                $chance *= ($safe - $steps + 1) / (MinerController::CELLS - $steps + 1);

                $expected = $chance * $method->invoke(new MinerController(), $steps, $mines);

                $this->assertLessThanOrEqual(
                    1.0,
                    $expected,
                    "Мин {$mines}, шагов {$steps}: шаг выгоден игроку"
                );
            }
        }
    }

    public function testPayoutDoesNotDependOnWhenPlayerStops(): void
    {
        // Множитель — обратный шанс дойти, поэтому ожидание одинаково на любом шаге
        foreach (MinerController::MINES as $mines) {
            $safe = MinerController::CELLS - $mines;

            foreach ([1, 3, 5, 8] as $steps) {
                $chance = 1.0;

                for ($step = 0; $step < $steps; $step++) {
                    $chance *= ($safe - $step) / (MinerController::CELLS - $step);
                }

                $payout = $this->payoutFor($mines, $steps);

                // Выплаты округлены до пяти сотых множителя, поэтому ожидание
                // гуляет на пару процентов, но нигде не выходит в плюс игроку
                $this->assertEqualsWithDelta(
                    96,
                    $chance * $payout,
                    3.0,
                    "Мин {$mines}, шагов {$steps}: ожидание уехало"
                );

                $this->assertLessThan(
                    100,
                    $chance * $payout,
                    "Мин {$mines}, шагов {$steps}: шаг стал выгоден игроку"
                );
            }
        }
    }

    /**
     * Выплата за ставку в 100 монет при заданном числе открытых клеток
     */
    private function payoutFor(int $mines, int $steps): int
    {
        $this->user->update(['money' => 100000]);
        session()->forget('miner');

        $this->actingAs($this->user)->post('/games/miner/bet', ['bet' => 100, 'mines' => $mines]);

        $before = $this->user->fresh()->money;

        for ($i = 0; $i < $steps; $i++) {
            $this->actingAs($this->user)->post('/games/miner/go', ['cell' => $this->safeCell()]);
        }

        $this->actingAs($this->user)->post('/games/miner/cash');

        return $this->user->fresh()->money - $before;
    }

    /**
     * Первая клетка без мины, которую ещё не открывали
     */
    private function safeCell(): int
    {
        $miner = session('miner');

        for ($cell = 0; $cell < MinerController::CELLS; $cell++) {
            if (! in_array($cell, $miner['field'], true) && ! in_array($cell, $miner['opened'], true)) {
                return $cell;
            }
        }

        $this->fail('Свободных клеток не осталось');
    }
}
