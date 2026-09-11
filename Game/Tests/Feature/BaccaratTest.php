<?php

namespace Modules\Game\Tests\Feature;

use App\Models\User;
use Modules\Game\Http\Controllers\BaccaratController;
use ReflectionMethod;
use Tests\ModuleTestCase;

class BaccaratTest extends ModuleTestCase
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
        $this->get('/games/baccarat')->assertForbidden();
    }

    public function testPageIsOpen(): void
    {
        $this->actingAs($this->user)->get('/games/baccarat')->assertOk();
    }

    public function testDealChargesBetAndReturnsHands(): void
    {
        $game = $this->round();

        $this->assertSame(100, $game['bet']);
        $this->assertSame('player', $game['type']);
        $this->assertSame(5000, $game['before']);
        $this->assertContains($game['result'], ['player', 'banker', 'tie']);

        $this->assertGreaterThanOrEqual(2, count($game['player']));
        $this->assertLessThanOrEqual(3, count($game['player']));
        $this->assertGreaterThanOrEqual(2, count($game['banker']));
        $this->assertLessThanOrEqual(3, count($game['banker']));

        // Карты не повторяются, обе руки берутся из одной колоды
        $cards = array_merge($game['player'], $game['banker']);
        $this->assertSame($cards, array_unique($cards));

        $this->assertSame(5000 - 100 + $game['win'], $this->user->fresh()->money);
    }

    public function testDealRejectsUnknownBetType(): void
    {
        $this->actingAs($this->user)
            ->post('/games/baccarat/deal', ['bet' => 100, 'type' => 'dealer'])
            ->assertRedirect('games/baccarat');

        $this->assertSame(5000, $this->user->fresh()->money);
        $this->assertNull(session('baccarat'));
        $this->assertNull(session('baccarat_result'));
    }

    public function testDealRejectsBetOverBalance(): void
    {
        $this->actingAs($this->user)
            ->post('/games/baccarat/deal', ['bet' => 5001, 'type' => 'banker'])
            ->assertRedirect('games/baccarat');

        $this->assertSame(5000, $this->user->fresh()->money);
        $this->assertNull(session('baccarat'));
        $this->assertNull(session('baccarat_result'));
    }

    public function testCardValues(): void
    {
        $method = $this->method('cardValue');
        $controller = new BaccaratController();

        // Карты идут четверками мастей: 1-4 двойки, 33-36 десятки, 49-52 тузы
        $this->assertSame(2, $method->invoke($controller, 1));
        $this->assertSame(9, $method->invoke($controller, 32));
        $this->assertSame(0, $method->invoke($controller, 33));
        $this->assertSame(0, $method->invoke($controller, 37));
        $this->assertSame(0, $method->invoke($controller, 48));
        $this->assertSame(1, $method->invoke($controller, 49));
        $this->assertSame(1, $method->invoke($controller, 52));
    }

    public function testTotalCountsModuloTen(): void
    {
        $method = $this->method('total');
        $controller = new BaccaratController();

        // Девятка и восьмерка дают 17 очков, значит рука стоит 7
        $this->assertSame(7, $method->invoke($controller, [32, 28]));

        // Десятка и король не считаются
        $this->assertSame(0, $method->invoke($controller, [33, 45]));
    }

    public function testBankerFollowsPlayerRulesWithoutThirdCard(): void
    {
        $method = $this->method('bankerDraws');
        $controller = new BaccaratController();

        foreach ([0, 1, 2, 3, 4, 5] as $total) {
            $this->assertTrue($method->invoke($controller, $total, null), "Банкир не взял карту при {$total}");
        }

        foreach ([6, 7] as $total) {
            $this->assertFalse($method->invoke($controller, $total, null), "Банкир взял лишнюю карту при {$total}");
        }
    }

    public function testBankerThirdCardTable(): void
    {
        $method = $this->method('bankerDraws');
        $controller = new BaccaratController();

        // Ключ — сумма банкира, значения — очки третьей карты игрока, на которых он берет карту
        $table = [
            0 => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            1 => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            2 => [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            3 => [0, 1, 2, 3, 4, 5, 6, 7, 9],
            4 => [2, 3, 4, 5, 6, 7],
            5 => [4, 5, 6, 7],
            6 => [6, 7],
            7 => [],
        ];

        foreach ($table as $bankerTotal => $draws) {
            for ($third = 0; $third <= 9; $third++) {
                $this->assertSame(
                    in_array($third, $draws, true),
                    $method->invoke($controller, $bankerTotal, $third),
                    "Банкир {$bankerTotal} против третьей карты {$third}"
                );
            }
        }
    }

    public function testNaturalStopsDealing(): void
    {
        $play = $this->method('play');
        $total = $this->method('total');
        $draw = $this->method('draw');
        $controller = new BaccaratController();

        $naturals = 0;

        for ($i = 0; $i < 200; $i++) {
            $deck = array_combine(range(1, 52), range(1, 52));
            $player = [$draw->invokeArgs($controller, [&$deck]), $draw->invokeArgs($controller, [&$deck])];
            $banker = [$draw->invokeArgs($controller, [&$deck]), $draw->invokeArgs($controller, [&$deck])];

            $hands = $play->invokeArgs($controller, [$deck, $player, $banker, $total->invoke($controller, $player) <= 5]);

            $playerStart = $total->invoke($controller, array_slice($hands['player'], 0, 2));
            $bankerStart = $total->invoke($controller, array_slice($hands['banker'], 0, 2));

            if ($playerStart < 8 && $bankerStart < 8) {
                // Игрок берет карту на 0-5 и только на них
                $this->assertSame($playerStart <= 5, count($hands['player']) === 3);

                continue;
            }

            $naturals++;

            $this->assertCount(2, $hands['player'], 'Карта взята поверх натурала');
            $this->assertCount(2, $hands['banker'], 'Карта взята поверх натурала');
        }

        $this->assertGreaterThan(0, $naturals, 'За 200 раздач натурал ни разу не выпал');
    }

    public function testPayouts(): void
    {
        $method = $this->method('payout');
        $controller = new BaccaratController();

        $this->assertSame(200, $method->invoke($controller, 'player', 'player', 100));

        // Комиссия 5% округляется в пользу заведения
        $this->assertSame(195, $method->invoke($controller, 'banker', 'banker', 100));
        $this->assertSame(19, $method->invoke($controller, 'banker', 'banker', 10));

        $this->assertSame(900, $method->invoke($controller, 'tie', 'tie', 100));

        // Ничья возвращает ставки на игрока и банкира
        $this->assertSame(100, $method->invoke($controller, 'player', 'tie', 100));
        $this->assertSame(100, $method->invoke($controller, 'banker', 'tie', 100));

        $this->assertSame(0, $method->invoke($controller, 'player', 'banker', 100));
        $this->assertSame(0, $method->invoke($controller, 'banker', 'player', 100));
        $this->assertSame(0, $method->invoke($controller, 'tie', 'player', 100));
    }

    /**
     * Ничья не должна списывать ставку на игрока
     */
    public function testTieReturnsBet(): void
    {
        $this->user->update(['money' => 100000]);

        for ($i = 0; $i < 300; $i++) {
            $before = $this->user->fresh()->money;

            $game = $this->round();

            if ($game['result'] === 'tie') {
                $this->assertSame(100, $game['win']);
                $this->assertSame($before, $this->user->fresh()->money);

                return;
            }
        }

        $this->markTestSkipped('За 300 раздач ничья не выпала');
    }

    /**
     * Партия на пяти очках должна остановиться и ждать игрока
     */
    public function testFivePointsWaitForDecision(): void
    {
        $this->user->update(['money' => 100000]);

        for ($i = 0; $i < 300; $i++) {
            $this->actingAs($this->user)->post('/games/baccarat/deal', ['bet' => 100, 'type' => 'player']);

            $pending = session('baccarat');

            if (! $pending) {
                continue;
            }

            $this->assertCount(2, $pending['player']);
            $this->assertCount(2, $pending['banker']);
            $this->assertArrayNotHasKey('result', $pending, 'Партия рассчиталась, не спросив игрока');

            // Пока решение не принято, деньги не двигаются
            $money = $this->user->fresh()->money;

            $this->actingAs($this->user)->post('/games/baccarat/decide');

            $game = session('baccarat_result');

            $this->assertCount(2, $game['player'], 'Отказ от карты всё равно добрал третью');
            $this->assertSame(5, $game['player_total']);
            $this->assertNull(session('baccarat'));
            $this->assertSame($money + $game['win'], $this->user->fresh()->money);

            return;
        }

        $this->markTestSkipped('За 300 раздач пятёрка игроку не пришла');
    }

    /**
     * Согласие берёт ровно одну карту
     */
    public function testDecisionDrawsSingleCard(): void
    {
        $this->user->update(['money' => 100000]);

        for ($i = 0; $i < 300; $i++) {
            $this->actingAs($this->user)->post('/games/baccarat/deal', ['bet' => 100, 'type' => 'player']);

            if (! session('baccarat')) {
                continue;
            }

            $this->actingAs($this->user)->post('/games/baccarat/decide', ['draw' => '1']);

            $game = session('baccarat_result');

            $this->assertCount(3, $game['player']);
            $this->assertNull(session('baccarat'));

            return;
        }

        $this->markTestSkipped('За 300 раздач пятёрка игроку не пришла');
    }

    /**
     * Решение без начатой партии ничего не меняет
     */
    public function testDecisionWithoutGameIsIgnored(): void
    {
        $this->actingAs($this->user)
            ->post('/games/baccarat/decide', ['draw' => '1'])
            ->assertRedirect('games/baccarat');

        $this->assertSame(5000, $this->user->fresh()->money);
        $this->assertNull(session('baccarat_result'));
    }

    /**
     * Раздача целиком: на пяти очках партия ждёт решения, дотягиваем карту
     */
    private function round(string $type = 'player', int $bet = 100): array
    {
        $this->actingAs($this->user)->post('/games/baccarat/deal', ['bet' => $bet, 'type' => $type]);

        if (session('baccarat')) {
            $this->actingAs($this->user)->post('/games/baccarat/decide', ['draw' => '1']);
        }

        return session('baccarat_result');
    }

    private function method(string $name): ReflectionMethod
    {
        return new ReflectionMethod(BaccaratController::class, $name);
    }
}
