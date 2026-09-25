<?php

namespace Modules\Game\Tests\Feature;

use App\Models\User;
use Illuminate\Testing\TestResponse;
use Modules\Game\Http\Controllers\GuessNumberController;
use Tests\ModuleTestCase;

class GuessNumberTest extends ModuleTestCase
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
        $this->get('/games/guess')->assertForbidden();
    }

    public function testPageIsOpen(): void
    {
        $this->actingAs($this->user)->get('/games/guess')->assertOk();
    }

    public function testEveryAttemptIsCharged(): void
    {
        $this->startGame();
        $this->guess(1);
        $this->assertSame(5000 - GuessNumberController::PRICE, $this->user->fresh()->money);

        $this->guess(2);
        $this->assertSame(5000 - 2 * GuessNumberController::PRICE, $this->user->fresh()->money);
    }

    public function testGameIsRejectedWithoutMoney(): void
    {
        $this->user->update(['money' => GuessNumberController::PRICE - 1]);

        $this->guess(1)->assertSessionHasErrors('guess');

        $this->assertSame(GuessNumberController::PRICE - 1, $this->user->fresh()->money);
        $this->assertNull(session('guess'));
    }

    public function testNumberOutOfRangeIsRejected(): void
    {
        // Загаданное число всегда внутри поля, поэтому промах мимо границ —
        // опечатка, а не ход: деньги за него не списываются
        foreach ([0, GuessNumberController::MAX + 1, -5] as $number) {
            $this->guess($number)->assertSessionHasErrors('guess');
        }

        $this->assertSame(5000, $this->user->fresh()->money);
        $this->assertNull(session('guess'));
    }

    public function testNonNumberIsRejected(): void
    {
        $this->guess('abc')->assertSessionHasErrors('guess');

        $this->assertSame(5000, $this->user->fresh()->money);
    }

    public function testNewGameHidesNumberInRange(): void
    {
        $this->guess(1);

        // Загаданную единицу первый ход угадывает сразу — партия закрыта, число ушло из сессии
        $number = session('guess.number') ?? 1;

        $this->assertGreaterThanOrEqual(GuessNumberController::MIN, $number);
        $this->assertLessThanOrEqual(GuessNumberController::MAX, $number);
    }

    public function testNumberIsKeptBetweenAttempts(): void
    {
        $this->startGame();
        $this->guess(1);
        $this->guess(2);

        $this->assertSame(50, session('guess.number'));
    }

    public function testHintsPointToTheNumber(): void
    {
        $this->startGame();
        $this->guess(1);
        $this->guess(99);

        $history = session('guess.history');

        $this->assertSame('more', $history[0]['hint']);
        $this->assertSame('less', $history[1]['hint']);
    }

    public function testWinPaysPrizeAndClosesGame(): void
    {
        $this->startGame();
        $this->guess(1);
        $money = $this->user->fresh()->money;

        $this->guess(50);

        $this->assertSame($money - GuessNumberController::PRICE + GuessNumberController::PRIZE, $this->user->fresh()->money);
        $this->assertNull(session('guess'));
    }

    public function testGameEndsAfterLastAttempt(): void
    {
        $this->startGame();

        foreach (range(1, GuessNumberController::TRIES) as $ignored) {
            $response = $this->guess(1);
        }

        $this->assertNull(session('guess'));
        $response->assertOk();
    }

    public function testAttemptsAreCountedDown(): void
    {
        $this->startGame();
        $this->guess(1);

        $this->assertSame(GuessNumberController::TRIES - 1, session('guess.try'));
    }

    public function testResetDropsTheGame(): void
    {
        $this->startGame();
        $this->guess(1);
        $this->assertNotNull(session('guess'));

        $this->actingAs($this->user)->post('/games/guess/reset')->assertRedirect('games/guess');

        $this->assertNull(session('guess'));
    }

    public function testAjaxReturnsPartial(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/games/guess/go', ['guess' => 1], ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk()->assertJson(['success' => true]);

        $html = $response->json('html');

        $this->assertStringContainsString('id="guess-box"', $html);
        $this->assertStringNotContainsString('<html', $html);
    }

    public function testUnfinishedNumberIsNotLeakedToPage(): void
    {
        $this->startGame();
        $this->guess(1);

        $html = $this->actingAs($this->user)->get('/games/guess')->getContent();

        // Подсказка «между» может совпасть с числом, поэтому ищем его как значение поля
        $this->assertStringNotContainsString('value="50"', $html);
    }

    public function testOptimalPlayWinsAboutThirdOfGames(): void
    {
        // Из сотни пятью делениями пополам отсекается лишь 31 число,
        // поэтому даже безошибочная игра выигрывает в трети партий
        $top = GuessNumberController::MAX - GuessNumberController::MIN + 1;
        $wins = 0;

        foreach (range(GuessNumberController::MIN, GuessNumberController::MAX) as $target) {
            $low = GuessNumberController::MIN;
            $high = GuessNumberController::MAX;

            foreach (range(1, GuessNumberController::TRIES) as $ignored) {
                $middle = intdiv($low + $high, 2);

                if ($middle === $target) {
                    $wins++;
                    break;
                }

                if ($middle < $target) {
                    $low = $middle + 1;
                } else {
                    $high = $middle - 1;
                }
            }
        }

        $this->assertEqualsWithDelta(0.31, $wins / $top, 0.02, 'Доля побед уехала от расчётной');
    }

    public function testPrizeDoesNotDependOnAttempt(): void
    {
        // Приз фиксированный: угадано с первой попытки или с последней — платится одно и то же
        foreach (range(1, GuessNumberController::TRIES - 1) as $misses) {
            $user = User::factory()->create(['money' => 5000]);
            $this->startGame();

            foreach (range(1, $misses) as $ignored) {
                $this->actingAs($user)->post('/games/guess/go', ['guess' => 1]);
            }

            $money = $user->fresh()->money;
            $this->actingAs($user)->post('/games/guess/go', ['guess' => 50]);

            $this->assertSame(
                $money - GuessNumberController::PRICE + GuessNumberController::PRIZE,
                $user->fresh()->money,
                "Приз после $misses промахов отличается от фиксированного",
            );
        }
    }

    public function testAjaxShowsValidationError(): void
    {
        // Страница не перезагружается, поэтому ошибка должна прийти json-ом,
        // иначе игрок жмёт кнопку и не понимает, почему ничего не происходит
        $response = $this->actingAs($this->user)->postJson(
            '/games/guess/go',
            ['guess' => GuessNumberController::MAX + 50],
            ['X-Requested-With' => 'XMLHttpRequest'],
        );

        $response->assertOk()->assertJson(['success' => false]);

        $this->assertNotEmpty($response->json('message'));
        $this->assertSame(5000, $this->user->fresh()->money);
    }

    /**
     * Начинает партию с известным числом: со случайным ход единицей
     * мог угадать его сразу и закрыть партию раньше, чем тест её проверит
     */
    private function startGame(int $number = 50): void
    {
        $this->withSession(['guess' => [
            'number'  => $number,
            'try'     => GuessNumberController::TRIES,
            'history' => [],
        ]]);
    }

    private function guess(mixed $number): TestResponse
    {
        return $this->actingAs($this->user)->post('/games/guess/go', ['guess' => $number]);
    }
}
