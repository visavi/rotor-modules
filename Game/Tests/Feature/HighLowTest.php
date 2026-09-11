<?php

namespace Modules\Game\Tests\Feature;

use App\Models\User;
use Modules\Game\Http\Controllers\HighLowController;
use ReflectionMethod;
use Tests\ModuleTestCase;

class HighLowTest extends ModuleTestCase
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
        $this->get('/games/highlow')->assertForbidden();
    }

    public function testPageIsOpen(): void
    {
        $this->actingAs($this->user)->get('/games/highlow')->assertOk();
    }

    public function testRulesAreOpen(): void
    {
        $this->actingAs($this->user)->get('/games/highlow/rules')->assertOk();
    }

    public function testBetChargesMoneyAndOpensCard(): void
    {
        $this->actingAs($this->user)
            ->post('/games/highlow/bet', ['bet' => 100])
            ->assertRedirect('games/highlow');

        $game = session('highlow');

        $this->assertSame(100, $game['bet']);
        $this->assertSame(5000, $game['before']);
        $this->assertCount(1, $game['cards']);
        $this->assertCount(HighLowController::DECK - 1, $game['deck']);
        $this->assertSame(1.0, $game['multiplier']);
        $this->assertSame(4900, $this->user->fresh()->money);
    }

    public function testSecondBetDoesNotChargeTwice(): void
    {
        $this->actingAs($this->user)->post('/games/highlow/bet', ['bet' => 100]);
        $this->actingAs($this->user)->post('/games/highlow/bet', ['bet' => 100]);

        $this->assertSame(4900, $this->user->fresh()->money, 'Ставка списана дважды');
    }

    public function testBetRejectsOverBalance(): void
    {
        $this->actingAs($this->user)
            ->post('/games/highlow/bet', ['bet' => 6000])
            ->assertSessionHasErrors('bet');

        $this->assertSame(5000, $this->user->fresh()->money);
    }

    public function testMoveWithoutGameIsIgnored(): void
    {
        $this->actingAs($this->user)
            ->post('/games/highlow/move', ['guess' => 'higher'])
            ->assertRedirect('games/highlow');

        $this->assertNull(session('highlow'));
    }

    public function testUnknownGuessIsIgnored(): void
    {
        $this->actingAs($this->user)->post('/games/highlow/bet', ['bet' => 100]);
        $before = session('highlow');

        $this->actingAs($this->user)->post('/games/highlow/move', ['guess' => 'sideways']);

        $this->assertSame($before['cards'], session('highlow')['cards'], 'Карта взята по неизвестной ставке');
    }

    public function testMoveTakesOneCard(): void
    {
        $this->actingAs($this->user)->post('/games/highlow/bet', ['bet' => 100]);
        $this->actingAs($this->user)->post('/games/highlow/move', ['guess' => 'higher']);

        $game = session('highlow') ?? session('highlow_result');

        $this->assertCount(2, $game['cards']);
        $this->assertSame(1, $game['shown'], 'Первая карта анимируется повторно');
    }

    public function testTableOffersBothSidesWithPrices(): void
    {
        $this->actingAs($this->user)->post('/games/highlow/bet', ['bet' => 100]);

        $content = (string) $this->actingAs($this->user)->get('/games/highlow')->getContent();

        // Цена шага видна до выбора, иначе играть вслепую
        $this->assertStringContainsString(__('game::games.hl_higher'), $content);
        $this->assertMatchesRegularExpression('/badge[^>]*>x[0-9.]+/', $content);
    }

    public function testOnlyFreshCardIsAnimated(): void
    {
        $this->actingAs($this->user)->post('/games/highlow/bet', ['bet' => 100]);

        // На тузе «больше» не предлагается, и ход бы не состоялся
        $guess = $this->rank(session('highlow')['cards'][0]) < 6 ? 'higher' : 'lower';

        $this->actingAs($this->user)->post('/games/highlow/move', ['guess' => $guess]);

        $content = (string) $this->actingAs($this->user)->get('/games/highlow')->getContent();

        $this->assertSame(1, substr_count($content, 'hl-deal"'), 'Анимируется не только новая карта');
    }

    public function testCashBeforeFirstCardIsIgnored(): void
    {
        $this->actingAs($this->user)->post('/games/highlow/bet', ['bet' => 100]);
        $this->actingAs($this->user)->post('/games/highlow/cash');

        // Возврат ставки — это не выигрыш, партия должна продолжаться
        $this->assertSame(4900, $this->user->fresh()->money);
        $this->assertNotNull(session('highlow'));
        $this->assertNull(session('highlow_result'));
    }

    public function testCashButtonAppearsOnlyAfterWonCard(): void
    {
        $this->actingAs($this->user)->post('/games/highlow/bet', ['bet' => 100]);

        $content = (string) $this->actingAs($this->user)->get('/games/highlow')->getContent();

        $this->assertStringNotContainsString('/games/highlow/cash', $content);
    }

    public function testCashPaysPotAndEndsGame(): void
    {
        $game = $this->playUntilMultiplierGrows();

        $this->actingAs($this->user)->post('/games/highlow/cash');

        $expected = 4900 + (int) (100 * $game['multiplier']);

        $this->assertSame($expected, $this->user->fresh()->money);
        $this->assertNull(session('highlow'));
        $this->assertSame('victory', session('highlow_result')['result']);
        $this->assertGreaterThan(100, session('highlow_result')['win'], 'Выигрыш не больше ставки');
    }

    public function testFinishedGameOffersNewBet(): void
    {
        $this->playUntilMultiplierGrows();
        $this->actingAs($this->user)->post('/games/highlow/cash');

        // После партии ставку можно поменять, а не только повторить прежнюю
        $this->actingAs($this->user)
            ->get('/games/highlow')
            ->assertOk()
            ->assertSee('name="bet"', false);
    }

    /**
     * Играет, пока не будет угадана карта: только тогда есть что забирать
     */
    private function playUntilMultiplierGrows(): array
    {
        for ($attempt = 0; $attempt < 100; $attempt++) {
            $this->user->update(['money' => 5000]);
            session()->forget(['highlow', 'highlow_result']);

            $this->actingAs($this->user)->post('/games/highlow/bet', ['bet' => 100]);

            $game = session('highlow');
            $guess = $this->rank($game['cards'][0]) < 6 ? 'higher' : 'lower';

            $this->actingAs($this->user)->post('/games/highlow/move', ['guess' => $guess]);

            $game = session('highlow');

            if ($game && $game['multiplier'] > 1.0) {
                return $game;
            }
        }

        $this->fail('За сто партий ни одна карта не была угадана');
    }

    public function testCashWithoutGameIsIgnored(): void
    {
        $this->actingAs($this->user)->post('/games/highlow/cash');

        $this->assertSame(5000, $this->user->fresh()->money);
    }

    public function testLossKeepsBetAndClearsGame(): void
    {
        // Ставка на «меньше» при открытой двойке проигрывает на любой другой карте
        for ($attempt = 0; $attempt < 60; $attempt++) {
            $this->user->update(['money' => 5000]);
            session()->forget('highlow');

            $this->actingAs($this->user)->post('/games/highlow/bet', ['bet' => 100]);

            $game = session('highlow');

            // Двойки — карты 1-4, на них «меньше» не предлагается вовсе
            if ($this->rank($game['cards'][0]) < 2) {
                continue;
            }

            $this->actingAs($this->user)->post('/games/highlow/move', ['guess' => 'lower']);

            $result = session('highlow_result');

            if ($result && $result['result'] === 'lost') {
                $this->assertSame(4900, $this->user->fresh()->money, 'Проигрыш вернул деньги');
                $this->assertNull(session('highlow'));

                return;
            }
        }

        $this->markTestSkipped('За шестьдесят партий проигрыш не выпал');
    }

    public function testDrawKeepsMultiplierAndBet(): void
    {
        for ($attempt = 0; $attempt < 200; $attempt++) {
            $this->user->update(['money' => 5000]);
            session()->forget('highlow');

            $this->actingAs($this->user)->post('/games/highlow/bet', ['bet' => 100]);
            $this->actingAs($this->user)->post('/games/highlow/move', ['guess' => 'higher']);

            $game = session('highlow');

            if ($game && ! empty($game['draw'])) {
                $this->assertSame(1.0, $game['multiplier'], 'Ничья изменила множитель');
                $this->assertSame(4900, $this->user->fresh()->money, 'Ничья тронула деньги');

                return;
            }
        }

        $this->markTestSkipped('За двести партий ничья не выпала');
    }

    public function testStepPriceLeavesHouseEdge(): void
    {
        $step = new ReflectionMethod(HighLowController::class, 'step');
        $controller = $this->app->make(HighLowController::class);

        // Ровно половина карт старше: честная цена шага — два, заведение платит меньше
        $chances = ['higher' => 20, 'lower' => 20, 'equal' => 3];

        $price = $step->invoke($controller, $chances, 'higher');

        $this->assertEqualsWithDelta(1.9, $price, 0.0001);
        $this->assertLessThan(2.0, $price, 'Шаг оплачивается без доли заведения');
    }

    public function testStepPriceGrowsWhenChanceFalls(): void
    {
        $step = new ReflectionMethod(HighLowController::class, 'step');
        $controller = $this->app->make(HighLowController::class);

        $easy = $step->invoke($controller, ['higher' => 30, 'lower' => 10, 'equal' => 0], 'higher');
        $hard = $step->invoke($controller, ['higher' => 10, 'lower' => 30, 'equal' => 0], 'higher');

        $this->assertLessThan($hard, $easy);
    }

    public function testEveryStepReturnsBelowOne(): void
    {
        $step = new ReflectionMethod(HighLowController::class, 'step');
        $controller = $this->app->make(HighLowController::class);

        // Возврат шага — цена, умноженная на шанс угадать: всегда ниже единицы
        for ($higher = 1; $higher <= 40; $higher++) {
            $chances = ['higher' => $higher, 'lower' => 41 - $higher, 'equal' => 0];
            $decisive = $chances['higher'] + $chances['lower'];

            $return = $step->invoke($controller, $chances, 'higher') * $higher / $decisive;

            $this->assertEqualsWithDelta(HighLowController::RETURN_RATE, $return, 0.0001);
        }
    }

    private function rank(int $card): int
    {
        return intdiv($card - 1, 4);
    }
}
