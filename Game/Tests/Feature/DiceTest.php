<?php

namespace Modules\Game\Tests\Feature;

use App\Models\User;
use Modules\Game\Http\Controllers\DiceController;
use ReflectionMethod;
use Tests\ModuleTestCase;

class DiceTest extends ModuleTestCase
{
    protected string $moduleName = 'Game';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->overrideSetting('bonusmoney', 0);

        $this->user = User::factory()->create(['money' => 1000]);
    }

    public function testPageNeedsAuth(): void
    {
        $this->get('/games/dices')->assertForbidden();
    }

    public function testPageIsOpen(): void
    {
        $this->actingAs($this->user)->get('/games/dices')->assertOk();
    }

    public function testRollIsRejectedWithoutMoney(): void
    {
        $this->user->update(['money' => 1]);

        $this->actingAs($this->user)
            ->post('/games/dices/roll')
            ->assertSee(__('game::games.cannot_play'));

        $this->assertSame(1, $this->user->fresh()->money);
    }

    public function testBalanceMatchesResult(): void
    {
        // Баланс меняется ровно на выигрыш или проигрыш, ничья не трогает деньги
        for ($i = 0; $i < 40; $i++) {
            $this->user->update(['money' => 1000]);

            $html = (string) $this->actingAs($this->user)
                ->postJson('/games/dices/roll', [], ['X-Requested-With' => 'XMLHttpRequest'])
                ->json('html');

            $money = $this->user->fresh()->money;

            if (str_contains($html, __('game::games.victory'))) {
                $this->assertSame(1000 - DiceController::BET + DiceController::WIN, $money);
            } elseif (str_contains($html, __('game::games.lost'))) {
                $this->assertSame(1000 - DiceController::BET, $money);
            } else {
                $this->assertSame(1000, $money, 'Ничья не вернула ставку');
            }
        }
    }

    public function testAjaxRollReturnsTableOnly(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/games/dices/roll', [], ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk()->assertJson(['success' => true]);

        $html = $response->json('html');

        $this->assertStringContainsString('id="dices-table"', $html);
        $this->assertStringNotContainsString('<html', $html, 'Ajax получил целую страницу');
    }

    public function testOldBalanceIsShownUntilDiceStop(): void
    {
        $html = (string) $this->actingAs($this->user)
            ->postJson('/games/dices/roll', [], ['X-Requested-With' => 'XMLHttpRequest'])
            ->json('html');

        $this->assertStringContainsString('dices-balance-old', $html);
        $this->assertStringContainsString(plural(1000, setting('moneyname')), $html);
    }

    public function testEveryFaceHasPicture(): void
    {
        foreach (range(1, DiceController::FACES) as $face) {
            $this->assertFileExists(base_path('modules/Game/resources/assets/dices/' . $face . '.svg'));
        }
    }

    public function testPlayerSixIsRarer(): void
    {
        $roll = new ReflectionMethod(DiceController::class, 'rollUser');
        $controller = $this->app->make(DiceController::class);

        $sixes = 0;
        $rolls = 12000;

        for ($i = 0; $i < $rolls; $i++) {
            $sixes += $roll->invoke($controller) === DiceController::FACES ? 1 : 0;
        }

        // Каждый пятый бросок идёт без шестёрки: шанс падает с 1/6 до 4/30
        $this->assertEqualsWithDelta($rolls * 4 / 30, $sixes, $rolls * 4 / 30 * 0.15);
    }

    public function testHouseKeepsAboutFivePercent(): void
    {
        $roll = new ReflectionMethod(DiceController::class, 'rollUser');
        $controller = $this->app->make(DiceController::class);

        $rounds = 40000;
        $paid = 0;

        for ($i = 0; $i < $rounds; $i++) {
            $user = $roll->invoke($controller) + $roll->invoke($controller);
            $banker = random_int(1, DiceController::FACES) + random_int(1, DiceController::FACES);

            $paid += match (true) {
                $user > $banker => DiceController::WIN,
                $user < $banker => 0,
                default         => DiceController::BET,
            };
        }

        // Возврат игроку около 95%: заведение забирает примерно двадцатую часть
        $returned = $paid / ($rounds * DiceController::BET);

        $this->assertEqualsWithDelta(0.95, $returned, 0.02);
        $this->assertLessThan(1.0, $returned, 'Игра раздаёт деньги');
    }

    public function testBankerDiceAreFair(): void
    {
        // Перевес держится только на кубике игрока, банкир играет честными
        $this->assertSame(6, DiceController::FACES);
        $this->assertSame(5, DiceController::FAIR_ROLLS);
    }
}
