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

    public function testPlayChargesBetAndDrawsNumbers(): void
    {
        $this->actingAs($this->user)
            ->post('/games/keno/play', ['bet' => 100, 'numbers' => $this->numbers()])
            ->assertRedirect('games/keno');

        $game = session('keno');

        $this->assertCount(KenoController::DRAW, $game['drawn']);
        $this->assertSame($game['drawn'], array_unique($game['drawn']), 'Число выпало дважды');
        $this->assertSame($this->numbers(), $game['picks']);
        $this->assertSame(5000, $game['before']);
        $this->assertSame(5000 - 100 + $game['win'], $this->user->fresh()->money);

        foreach ($game['drawn'] as $number) {
            $this->assertGreaterThanOrEqual(1, $number);
            $this->assertLessThanOrEqual(KenoController::FIELD, $number);
        }
    }

    public function testMatchedNumbersAreDrawnAndPicked(): void
    {
        $this->actingAs($this->user)->post('/games/keno/play', ['bet' => 100, 'numbers' => $this->numbers()]);

        $game = session('keno');

        foreach ($game['matched'] as $number) {
            $this->assertContains($number, $game['picks']);
            $this->assertContains($number, $game['drawn']);
        }

        $this->assertSame(
            array_values(array_intersect($game['picks'], $game['drawn'])),
            $game['matched'],
        );
    }

    public function testPlayNeedsExactCountOfNumbers(): void
    {
        $picks = KenoController::PICKS;

        foreach ([[7], range(1, $picks - 1), range(1, $picks + 1)] as $numbers) {
            $this->actingAs($this->user)
                ->post('/games/keno/play', ['bet' => 100, 'numbers' => $numbers])
                ->assertSessionHasErrors('numbers');
        }

        $this->assertSame(5000, $this->user->fresh()->money);
    }

    public function testPlayRejectsBetOverBalance(): void
    {
        $this->actingAs($this->user)
            ->post('/games/keno/play', ['bet' => 6000, 'numbers' => $this->numbers()])
            ->assertSessionHasErrors('bet');

        $this->assertSame(5000, $this->user->fresh()->money);
    }

    public function testPlayRejectsEmptyBet(): void
    {
        $this->actingAs($this->user)
            ->post('/games/keno/play', ['bet' => 0, 'numbers' => $this->numbers()])
            ->assertSessionHasErrors('bet');
    }

    public function testDuplicatesAndOutOfFieldNumbersAreDropped(): void
    {
        // Одно число, присланное дважды, удваивало бы шанс совпадения
        $this->actingAs($this->user)
            ->post('/games/keno/play', [
                'bet'     => 100,
                'numbers' => [5, 5, 5, 1, 2, 3, KenoController::FIELD + 1, 0, -3],
            ])
            ->assertSessionHasErrors('numbers');

        $this->assertSame(5000, $this->user->fresh()->money);
    }

    public function testPayoutsRiseWithEveryHit(): void
    {
        // Плато из одинаковых множителей обесценивает лишнее угаданное число,
        // а самая нижняя ступень обязана платить больше ставки
        $previous = 1.0;

        foreach (KenoController::PAYOUTS as $hits => $multiplier) {
            $this->assertGreaterThan($previous, $multiplier, "Выплата за $hits совпадений не выросла");
            $previous = $multiplier;
        }
    }

    public function testRarerHitsPayMore(): void
    {
        // Чем реже совпадение, тем крупнее выплата
        $controller = $this->app->make(KenoController::class);
        $previous = 1.0;

        foreach (array_keys(KenoController::PAYOUTS) as $hits) {
            $chance = $controller->chance($hits);

            $this->assertLessThan($previous, $chance, "Совпадение $hits встречается не реже предыдущего");
            $previous = $chance;
        }
    }

    public function testWinIsNearlyAsLikelyAsLoss(): void
    {
        $controller = $this->app->make(KenoController::class);
        $win = $controller->frequency();

        // Любая выплата больше ставки, поэтому частота выплат — это и есть
        // частота выигрыша. Игрок должен выигрывать почти так же часто, как
        // проигрывать: перевес заведения около пяти процентов, не больше
        $this->assertGreaterThan(1, min(KenoController::PAYOUTS), 'Выплата не больше ставки');
        $this->assertEqualsWithDelta(0.475, $win, 0.03, 'Выигрыш и проигрыш разошлись больше чем на 5%');
    }

    public function testHouseEdgeStaysInRange(): void
    {
        $controller = $this->app->make(KenoController::class);
        $payback = 0.0;

        foreach (KenoController::PAYOUTS as $hits => $multiplier) {
            $payback += $controller->chance($hits) * $multiplier;
        }

        $this->assertGreaterThan(0.93, $payback, 'Возврат слишком мал');
        $this->assertLessThan(0.97, $payback, 'Возврат больше доли заведения');
    }

    public function testChanceMatchesHypergeometricDistribution(): void
    {
        $controller = $this->app->make(KenoController::class);
        $total = 0.0;

        foreach (range(0, KenoController::PICKS) as $hits) {
            $chance = $controller->chance($hits);
            $this->assertGreaterThanOrEqual(0.0, $chance);
            $total += $chance;
        }

        // Все исходы вместе дают единицу — иначе распределение посчитано неверно
        $this->assertEqualsWithDelta(1.0, $total, 0.000001);
    }

    /**
     * Набор отметок нужного размера
     *
     * @return list<int>
     */
    private function numbers(): array
    {
        return range(1, KenoController::PICKS);
    }

    public function testAjaxReturnsBoardOnly(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/games/keno/play', ['bet' => 100, 'numbers' => $this->numbers()], ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk()->assertJson(['success' => true]);

        $html = $response->json('html');

        $this->assertStringContainsString('id="keno-box"', $html);
        $this->assertStringNotContainsString('<html', $html);
    }
}
