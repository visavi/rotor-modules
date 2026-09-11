<?php

namespace Modules\Game\Tests\Feature;

use App\Models\User;
use Modules\Game\Http\Controllers\KenoController;
use Tests\ModuleTestCase;

class KenoTest extends ModuleTestCase
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
        $this->get('/games/keno')->assertForbidden();
    }

    public function testPageIsOpen(): void
    {
        $this->actingAs($this->user)->get('/games/keno')->assertOk();
    }

    public function testRulesAreOpen(): void
    {
        $this->actingAs($this->user)->get('/games/keno/rules')->assertOk();
    }

    public function testPlayChargesBetAndDrawsTwentyNumbers(): void
    {
        $this->actingAs($this->user)
            ->post('/games/keno/play', ['bet' => 100, 'numbers' => [1, 2, 3, 4, 5]])
            ->assertRedirect('games/keno');

        $game = session('keno');

        $this->assertCount(KenoController::DRAW, $game['drawn']);
        $this->assertSame($game['drawn'], array_unique($game['drawn']), 'Число выпало дважды');
        $this->assertSame([1, 2, 3, 4, 5], $game['picks']);
        $this->assertSame(5000, $game['before']);
        $this->assertSame(5000 - 100 + $game['win'], $this->user->fresh()->money);

        foreach ($game['drawn'] as $number) {
            $this->assertGreaterThanOrEqual(1, $number);
            $this->assertLessThanOrEqual(KenoController::FIELD, $number);
        }
    }

    public function testFieldMarksDrawnNumbers(): void
    {
        $this->actingAs($this->user)->post('/games/keno/play', ['bet' => 100, 'numbers' => [1, 2, 3]]);

        $game = session('keno');

        // Каждое вытянутое число подсвечено и знает свой номер в тираже
        $content = (string) $this->actingAs($this->user)->get('/games/keno')->getContent();

        $this->assertStringContainsString('keno-drawn', $content);

        foreach ($game['drawn'] as $step => $number) {
            $this->assertStringContainsString('style="--step: ' . $step . '"', $content);
        }
    }

    public function testPlayRejectsTooFewNumbers(): void
    {
        $this->actingAs($this->user)
            ->post('/games/keno/play', ['bet' => 100, 'numbers' => [7]])
            ->assertSessionHasErrors('numbers');

        $this->assertSame(5000, $this->user->fresh()->money, 'Ставка списана при отказе');
    }

    public function testPlayRejectsTooManyNumbers(): void
    {
        $this->actingAs($this->user)
            ->post('/games/keno/play', ['bet' => 100, 'numbers' => range(1, 11)])
            ->assertSessionHasErrors('numbers');
    }

    public function testPlayRejectsBetOverBalance(): void
    {
        $this->actingAs($this->user)
            ->post('/games/keno/play', ['bet' => 6000, 'numbers' => [1, 2, 3]])
            ->assertSessionHasErrors('bet');
    }

    public function testPlayRejectsEmptyBet(): void
    {
        $this->actingAs($this->user)
            ->post('/games/keno/play', ['bet' => 0, 'numbers' => [1, 2, 3]])
            ->assertSessionHasErrors('bet');
    }

    public function testDuplicatesAndOutOfFieldNumbersAreDropped(): void
    {
        // Повтор давал бы два шанса на одно число, а число вне поля не выпадет никогда
        $this->actingAs($this->user)
            ->post('/games/keno/play', ['bet' => 100, 'numbers' => [5, 5, 5, 90, 0, -3, 7]]);

        $this->assertSame([5, 7], session('keno')['picks']);
    }

    public function testMatchedNumbersAreDrawnAndPicked(): void
    {
        $this->actingAs($this->user)
            ->post('/games/keno/play', ['bet' => 100, 'numbers' => range(1, 10)]);

        $game = session('keno');

        foreach ($game['matched'] as $number) {
            $this->assertContains($number, $game['picks']);
            $this->assertContains($number, $game['drawn']);
        }

        $this->assertSame(
            count(array_intersect($game['picks'], $game['drawn'])),
            count($game['matched']),
        );
    }

    public function testPayoutTableIsMonotonic(): void
    {
        foreach (KenoController::PAYOUTS as $picked => $table) {
            $previous = 0;

            foreach ($table as $hits => $multiplier) {
                $this->assertLessThanOrEqual($picked, $hits, 'Выплата за больше совпадений, чем отмечено');
                $this->assertGreaterThanOrEqual($previous, $multiplier, "Выплата падает: $picked/$hits");
                $previous = $multiplier;
            }
        }
    }

    public function testEveryRowPaysOftenEnough(): void
    {
        // Игра, которая почти никогда ничего не отдаёт, не игра, а автомат для сжигания монет
        foreach (array_keys(KenoController::PAYOUTS) as $picked) {
            $frequency = $this->app->make(KenoController::class)->frequency($picked);

            $this->assertGreaterThan(0.25, $frequency, "Строка $picked платит слишком редко");
        }
    }

    public function testFrequencyMatchesPayoutTable(): void
    {
        $controller = $this->app->make(KenoController::class);

        foreach (KenoController::PAYOUTS as $picked => $table) {
            $expected = 0.0;

            foreach (array_keys($table) as $hits) {
                $expected += $this->probability($picked, $hits);
            }

            $this->assertEqualsWithDelta($expected, $controller->frequency($picked), 0.0001);
        }
    }

    /**
     * Точный возврат по каждой строке таблицы
     *
     * Вероятность ровно k совпадений при m отмеченных числах —
     * гипергеометрическое распределение. Если кто-то поправит множитель
     * на глаз, тест покажет, во что это обошлось заведению
     */
    public function testHouseEdgeStaysInRange(): void
    {
        foreach (KenoController::PAYOUTS as $picked => $table) {
            $rtp = 0.0;

            foreach ($table as $hits => $multiplier) {
                $rtp += $this->probability($picked, $hits) * $multiplier;
            }

            $this->assertGreaterThan(0.93, $rtp, "Возврат по $picked числам слишком мал");
            $this->assertLessThan(0.97, $rtp, "Возврат по $picked числам больше заведения");
        }
    }

    public function testEveryDrawLandsInPayoutTable(): void
    {
        // Ни одна строка не должна платить за число совпадений, которого не бывает
        foreach (KenoController::PAYOUTS as $picked => $table) {
            foreach ($table as $hits => $multiplier) {
                $this->assertGreaterThan(0.0, $this->probability($picked, $hits));
            }
        }
    }

    /**
     * Вероятность ровно $hits совпадений при $picked отмеченных числах
     */
    private function probability(int $picked, int $hits): float
    {
        $field = KenoController::FIELD;
        $draw = KenoController::DRAW;

        return $this->binomial($picked, $hits)
            * $this->binomial($field - $picked, $draw - $hits)
            / $this->binomial($field, $draw);
    }

    private function binomial(int $n, int $k): float
    {
        if ($k < 0 || $k > $n) {
            return 0.0;
        }

        $result = 1.0;

        for ($i = 1; $i <= $k; $i++) {
            $result = $result * ($n - $k + $i) / $i;
        }

        return $result;
    }
}
