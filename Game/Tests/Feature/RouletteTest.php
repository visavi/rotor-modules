<?php

namespace Modules\Game\Tests\Feature;

use App\Models\User;
use Modules\Game\Http\Controllers\RouletteController;
use Tests\ModuleTestCase;

class RouletteTest extends ModuleTestCase
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
        $this->get('/games/roulette')->assertForbidden();
    }

    public function testPageIsOpen(): void
    {
        $this->actingAs($this->user)->get('/games/roulette')->assertOk();
    }

    public function testSpinChargesBetAndReturnsResult(): void
    {
        $this->actingAs($this->user)
            ->post('/games/roulette/spin', ['bet' => 100, 'type' => 'red'])
            ->assertRedirect('games/roulette');

        $spin = session('roulette');

        $this->assertGreaterThanOrEqual(0, $spin['number']);
        $this->assertLessThanOrEqual(36, $spin['number']);
        $this->assertSame(100, $spin['bet']);
        $this->assertSame(5000, $spin['before'], 'Баланс до спина нужен, чтобы не выдать исход во время анимации');
        $this->assertSame('red', $spin['type']);

        $expected = 5000 - 100 + $spin['win'];

        $this->assertSame($expected, $this->user->fresh()->money);
    }

    public function testSpinRejectsUnknownBetType(): void
    {
        $this->actingAs($this->user)
            ->post('/games/roulette/spin', ['bet' => 100, 'type' => 'purple'])
            ->assertRedirect('games/roulette');

        $this->assertSame(5000, $this->user->fresh()->money);
        $this->assertNull(session('roulette'));
    }

    public function testSpinRejectsBetOverBalance(): void
    {
        $this->actingAs($this->user)
            ->post('/games/roulette/spin', ['bet' => 5001, 'type' => 'red'])
            ->assertRedirect('games/roulette');

        $this->assertSame(5000, $this->user->fresh()->money);
        $this->assertNull(session('roulette'));
    }

    public function testSpinRejectsEmptyBet(): void
    {
        $this->actingAs($this->user)
            ->post('/games/roulette/spin', ['bet' => 0, 'type' => 'red'])
            ->assertRedirect('games/roulette');

        $this->assertSame(5000, $this->user->fresh()->money);
    }

    public function testNumberBetNeedsNumberInRange(): void
    {
        $this->actingAs($this->user)
            ->post('/games/roulette/spin', ['bet' => 100, 'type' => 'number', 'number' => 37])
            ->assertRedirect('games/roulette');

        $this->assertSame(5000, $this->user->fresh()->money);
        $this->assertNull(session('roulette'));
    }

    /**
     * Выплата по каждому типу ставки при известном выпавшем числе
     */
    public function testPayoutsMatchWinningNumber(): void
    {
        $controller = new RouletteController();
        $method = new \ReflectionMethod(RouletteController::class, 'isWin');

        $cases = [
            'red'     => [[1, 18, 36], [0, 2, 17]],
            'black'   => [[2, 17, 35], [0, 1, 18]],
            'even'    => [[2, 18, 36], [0, 1, 35]],
            'odd'     => [[1, 17, 35], [0, 2, 36]],
            'low'     => [[1, 18], [0, 19, 36]],
            'high'    => [[19, 36], [0, 1, 18]],
            'dozen1'  => [[1, 12], [0, 13, 36]],
            'dozen2'  => [[13, 24], [0, 12, 25]],
            'dozen3'  => [[25, 36], [0, 24, 12]],
            'column1' => [[1, 4, 34], [0, 2, 3]],
            'column2' => [[2, 5, 35], [0, 1, 3]],
            'column3' => [[3, 6, 36], [0, 1, 2]],
        ];

        foreach ($cases as $type => [$wins, $losses]) {
            foreach ($wins as $number) {
                $this->assertTrue($method->invoke($controller, $type, $number, 0), "{$type}: {$number} должно выигрывать");
            }

            foreach ($losses as $number) {
                $this->assertFalse($method->invoke($controller, $type, $number, 0), "{$type}: {$number} не должно выигрывать");
            }
        }

        $this->assertTrue($method->invoke($controller, 'number', 17, 17));
        $this->assertFalse($method->invoke($controller, 'number', 17, 18));
        $this->assertTrue($method->invoke($controller, 'number', 0, 0));
    }

    /**
     * Зеро забирает все ставки, кроме ставки на само зеро
     */
    public function testZeroBeatsOutsideBets(): void
    {
        $method = new \ReflectionMethod(RouletteController::class, 'isWin');
        $controller = new RouletteController();

        foreach (array_keys(RouletteController::BETS) as $type) {
            if ($type === 'number') {
                continue;
            }

            $this->assertFalse($method->invoke($controller, $type, 0, 0), "Зеро проиграло ставке {$type}");
        }
    }

    /**
     * На каждое число колеса приходится ровно один сектор
     */
    public function testWheelHoldsEveryNumberOnce(): void
    {
        $wheel = RouletteController::WHEEL;

        $this->assertCount(37, $wheel);
        $this->assertSame(range(0, 36), collect($wheel)->sort()->values()->all());
    }

    /**
     * Каждый выигрыш возвращает ставку и приносит прибыль
     */
    public function testWinPaysByMultiplier(): void
    {
        $this->user->update(['money' => 100000]);

        // Ставка на все числа сразу невозможна, поэтому крутим до попадания
        for ($i = 0; $i < 200; $i++) {
            $before = $this->user->fresh()->money;

            $this->actingAs($this->user)->post('/games/roulette/spin', ['bet' => 100, 'type' => 'number', 'number' => 7]);

            $spin = session('roulette');

            if ($spin['number'] === 7) {
                $this->assertSame(3600, $spin['win']);
                $this->assertSame($before - 100 + 3600, $this->user->fresh()->money);

                return;
            }

            $this->assertSame(0, $spin['win']);
            $this->assertSame($before - 100, $this->user->fresh()->money);
        }

        $this->markTestSkipped('За 200 спинов семёрка не выпала');
    }

    public function testAjaxReturnsWheelOnly(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/games/roulette/spin', ['bet' => 100, 'type' => 'red'], ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk()->assertJson(['success' => true]);

        $html = $response->json('html');

        $this->assertStringContainsString('id="roulette-box"', $html);
        $this->assertStringNotContainsString('<html', $html);
    }
}
