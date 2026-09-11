<?php

namespace Modules\Game\Tests\Feature;

use App\Models\User;
use Illuminate\Testing\TestResponse;
use Modules\Game\Http\Controllers\ThimbleController;
use Tests\ModuleTestCase;

class ThimbleTest extends ModuleTestCase
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
        $this->get('/games/thimbles')->assertForbidden();
    }

    public function testPageIsOpen(): void
    {
        $this->actingAs($this->user)->get('/games/thimbles')->assertOk();
    }

    public function testGameIsNotPlayedByGet(): void
    {
        $this->actingAs($this->user)->get('/games/thimbles/go?thimble=1')->assertMethodNotAllowed();

        $this->assertSame(5000, $this->user->fresh()->money);
    }

    public function testBetIsAlwaysCharged(): void
    {
        $this->play(1);

        $money = $this->user->fresh()->money;

        // Либо ставка списана, либо списана и сразу выплачен выигрыш
        $this->assertContains($money, [
            5000 - ThimbleController::BET,
            5000 - ThimbleController::BET + ThimbleController::WIN,
        ]);
    }

    public function testWrongThimbleIsRejected(): void
    {
        foreach ([0, ThimbleController::THIMBLES + 1, -1] as $thimble) {
            $this->play($thimble)->assertSessionHasErrors('thimble');
        }

        $this->assertSame(5000, $this->user->fresh()->money);
    }

    public function testNonNumberIsRejected(): void
    {
        $this->play('abc')->assertSessionHasErrors('thimble');

        $this->assertSame(5000, $this->user->fresh()->money);
    }

    public function testGameIsRejectedWithoutMoney(): void
    {
        $this->user->update(['money' => ThimbleController::BET - 1]);

        $this->play(1)->assertSessionHasErrors('thimble');

        $this->assertSame(ThimbleController::BET - 1, $this->user->fresh()->money);
    }

    public function testAjaxReturnsTableOnly(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/games/thimbles/go', ['thimble' => 2], ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk()->assertJson(['success' => true]);

        $html = $response->json('html');

        $this->assertStringContainsString('id="thimbles-box"', $html);
        $this->assertStringNotContainsString('<html', $html);
    }

    /**
     * Возврат игроку около 95%
     *
     * Шарик уводят в каждой двадцатой партии, поэтому выигрыш случается
     * не в трети партий, а реже — иначе выплата 15 за ставку 5 возвращала
     * ровно сто процентов и деньги в игре не убывали
     */
    public function testWinIsNearlyAsLikelyAsLoss(): void
    {
        // Шанс задан прямо, а не числом напёрстков: игрок выигрывает почти
        // так же часто, как проигрывает, и теряет около 5% ставки
        $chance = ThimbleController::WIN_CHANCE / 1000;
        $payback = $chance * ThimbleController::WIN / ThimbleController::BET;

        $this->assertEqualsWithDelta(0.475, $chance, 0.03, 'Выигрыш и проигрыш разошлись больше чем на 5%');
        $this->assertEqualsWithDelta(0.95, $payback, 0.01);
    }

    public function testBallFollowsTheDeclaredChance(): void
    {
        // Шарик кладут после выбора, поэтому доля побед должна сойтись
        // с объявленным шансом, а не с одной третью
        $method = new \ReflectionMethod(ThimbleController::class, 'ball');
        $controller = $this->app->make(ThimbleController::class);
        $rounds = 4000;
        $wins = 0;

        foreach (range(1, $rounds) as $ignored) {
            $ball = $method->invoke($controller, 2);

            $this->assertGreaterThanOrEqual(1, $ball);
            $this->assertLessThanOrEqual(ThimbleController::THIMBLES, $ball);

            if ($ball === 2) {
                $wins++;
            }
        }

        $this->assertEqualsWithDelta(ThimbleController::WIN_CHANCE / 1000, $wins / $rounds, 0.03);
    }

    private function play(mixed $thimble): TestResponse
    {
        return $this->actingAs($this->user)->post('/games/thimbles/go', ['thimble' => $thimble]);
    }
}
