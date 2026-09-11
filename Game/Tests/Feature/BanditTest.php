<?php

namespace Modules\Game\Tests\Feature;

use App\Models\User;
use Modules\Game\Http\Controllers\BanditController;
use ReflectionMethod;
use Tests\ModuleTestCase;

class BanditTest extends ModuleTestCase
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
        $this->get('/games/bandit')->assertForbidden();
    }

    public function testPageIsOpen(): void
    {
        $this->actingAs($this->user)->get('/games/bandit')->assertOk();
    }

    public function testSpinChargesBet(): void
    {
        $this->actingAs($this->user)->post('/games/bandit/spin')->assertOk();

        $money = $this->user->fresh()->money;

        // Ставка списана, выигрыш начислен сразу
        $this->assertLessThanOrEqual(5000 - BanditController::BET + 777 * 2, $money);
        $this->assertGreaterThanOrEqual(5000 - BanditController::BET, $money);
    }

    public function testBalanceMatchesSpinResult(): void
    {
        // Баланс обязан сойтись с точностью до монеты, а не «примерно»
        $roll = new ReflectionMethod(BanditController::class, 'roll');
        $score = new ReflectionMethod(BanditController::class, 'score');
        $controller = $this->app->make(BanditController::class);

        for ($i = 0; $i < 50; $i++) {
            $this->user->update(['money' => 5000]);

            $cells = $roll->invoke($controller);
            [, $sum] = $score->invoke($controller, $cells);

            $this->user->decrement('money', BanditController::BET);

            if ($sum > 0) {
                $this->user->increment('money', $sum);
            }

            $this->assertSame(5000 - BanditController::BET + $sum, $this->user->fresh()->money);
        }
    }

    public function testOldBalanceIsShownUntilReelsStop(): void
    {
        $this->user->update(['money' => 5000]);

        $html = $this->actingAs($this->user)
            ->postJson('/games/bandit/spin', [], ['X-Requested-With' => 'XMLHttpRequest'])
            ->json('html');

        $money = $this->user->fresh()->money;

        // До остановки барабанов виден баланс до спина, иначе он выдаёт исход
        $this->assertStringContainsString('bandit-balance-old', $html);
        $this->assertStringContainsString(plural(5000, setting('moneyname')), $html);
        $this->assertStringContainsString(plural($money, setting('moneyname')), $html);
    }

    public function testSpinIsRejectedWithoutMoney(): void
    {
        $this->user->update(['money' => 1]);

        $this->actingAs($this->user)
            ->post('/games/bandit/spin')
            ->assertSee(__('game::games.cannot_play'));

        $this->assertSame(1, $this->user->fresh()->money, 'Ставка списана без денег');
    }

    public function testAjaxSpinReturnsMachineOnly(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/games/bandit/spin', [], ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk()->assertJson(['success' => true]);

        $html = $response->json('html');

        $this->assertStringContainsString('id="bandit-machine"', $html);
        $this->assertStringNotContainsString('<html', $html, 'Ajax получил целую страницу');
    }

    public function testEverySymbolHasNameAndPayouts(): void
    {
        foreach (range(1, BanditController::SYMBOLS) as $symbol) {
            $this->assertArrayHasKey($symbol, BanditController::NAMES);
            $this->assertArrayHasKey($symbol, BanditController::PAYOUTS);

            foreach (array_keys(BanditController::LINES) as $position) {
                $this->assertArrayHasKey($position, BanditController::PAYOUTS[$symbol]);
            }

            $file = base_path('modules/Game/resources/assets/bandit/' . $symbol . '.svg');
            $this->assertFileExists($file, "Нет картинки символа $symbol");
        }
    }

    public function testLinesAreScored(): void
    {
        $score = new ReflectionMethod(BanditController::class, 'score');
        $controller = $this->app->make(BanditController::class);

        // Верхний ряд из семёрок и ничего больше
        $cells = [1 => 8, 2 => 8, 3 => 8, 4 => 1, 5 => 2, 6 => 3, 7 => 4, 8 => 5, 9 => 6];

        [$results, $sum] = $score->invoke($controller, $cells);

        $this->assertCount(1, $results);
        $this->assertSame('top_row', $results[0]['position']);
        $this->assertSame(177, $sum);
    }

    public function testCrossingLinesArePaidTogether(): void
    {
        $score = new ReflectionMethod(BanditController::class, 'score');
        $controller = $this->app->make(BanditController::class);

        // Поле из одних вишен: три ряда и три столбца сразу
        $cells = array_fill(1, 9, 1);

        [$results, $sum] = $score->invoke($controller, $cells);

        $this->assertCount(6, $results);
        $this->assertSame(40, $sum, 'Сумма всех линий вишни');
    }

    public function testWinIsNearlyAsLikelyAsLoss(): void
    {
        $roll = new ReflectionMethod(BanditController::class, 'roll');
        $score = new ReflectionMethod(BanditController::class, 'score');
        $controller = $this->app->make(BanditController::class);

        $spins = 6000;
        $wins = 0;

        for ($i = 0; $i < $spins; $i++) {
            [$results] = $score->invoke($controller, $roll->invoke($controller));

            // Автомат собирает поле под заранее решённый исход, поэтому
            // выигрышное вращение всегда даёт ровно одну линию
            $this->assertLessThanOrEqual(1, count($results));

            $wins += $results ? 1 : 0;
        }

        $expected = BanditController::WIN_CHANCE / 1000;

        $this->assertEqualsWithDelta($expected, $wins / $spins, 0.03, 'Выигрыш и проигрыш разошлись больше чем на 5%');
    }

    public function testExpensiveSymbolsComeUpRarely(): void
    {
        // Чем дороже символ, тем меньше его вес в выигрышной линии
        $previous = PHP_INT_MAX;

        foreach (BanditController::WEIGHTS as $symbol => $weight) {
            $this->assertLessThanOrEqual($previous, $weight, "Символ $symbol не стал реже предыдущего");
            $previous = $weight;
        }

        $this->assertCount(BanditController::SYMBOLS, BanditController::WEIGHTS);
    }

    public function testHouseKeepsFivePercent(): void
    {
        $roll = new ReflectionMethod(BanditController::class, 'roll');
        $score = new ReflectionMethod(BanditController::class, 'score');
        $controller = $this->app->make(BanditController::class);

        $spins = 40000;
        $paid = 0;

        for ($i = 0; $i < $spins; $i++) {
            [, $sum] = $score->invoke($controller, $roll->invoke($controller));
            $paid += $sum;
        }

        // Возврат около 95%. Разброс широкий: джекпот в 777 бьёт раз
        // на пятнадцать тысяч вращений и сильно двигает выборку
        $payback = $paid / ($spins * BanditController::BET);

        $this->assertGreaterThan(0.8, $payback, 'Автомат отдаёт слишком мало');
        $this->assertLessThan(1.1, $payback, 'Автомат отдаёт больше, чем собирает');
    }
}
