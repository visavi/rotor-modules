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

    public function testNumberIsKeptBetweenAttempts(): void
    {
        $this->guess(1);
        $number = session('guess.number');

        $this->guess(2);

        $this->assertSame($number, session('guess.number'));
    }

    public function testHintsPointToTheNumber(): void
    {
        $this->guess(1);

        // Единицей можно угадать с первой попытки — тогда партия закрыта,
        // а число уходит из сессии вместе с историей
        $number = session('guess.number');

        if ($number === null) {
            $this->assertSame(
                5000 - GuessNumberController::PRICE + GuessNumberController::PRIZE,
                $this->user->fresh()->money,
            );

            return;
        }

        // Число загадано случайно, поэтому проверяем подсказку по нему самому
        $history = session('guess.history');

        $this->assertSame($number > 1 ? 'more' : 'less', $history[0]['hint']);
    }

    public function testWinPaysPrizeAndClosesGame(): void
    {
        $this->guess(1);
        $number = session('guess.number');
        $money = $this->user->fresh()->money;

        $this->guess($number);

        $this->assertSame($money - GuessNumberController::PRICE + GuessNumberController::PRIZE, $this->user->fresh()->money);
        $this->assertNull(session('guess'));
    }

    public function testGameEndsAfterLastAttempt(): void
    {
        $this->guess(1);
        $number = session('guess.number');

        // Заведомо мимо: соседнее число всегда в диапазоне
        $wrong = $number === GuessNumberController::MAX ? $number - 1 : $number + 1;

        // Первая попытка уже сделана, до конца партии осталось TRIES - 1
        foreach (range(2, GuessNumberController::TRIES) as $ignored) {
            $response = $this->guess($wrong);
        }

        $this->assertNull(session('guess'));
        $response->assertOk();
    }

    public function testAttemptsAreCountedDown(): void
    {
        $this->guess(1);

        $this->assertSame(GuessNumberController::TRIES - 1, session('guess.try'));
    }

    public function testResetDropsTheGame(): void
    {
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
        $this->guess(1);
        $number = session('guess.number');

        $html = $this->actingAs($this->user)->get('/games/guess')->getContent();

        // Подсказка «между» может совпасть с числом, поэтому ищем его как значение поля
        $this->assertStringNotContainsString('value="' . $number . '"', $html);
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

            $this->actingAs($user)->post('/games/guess/go', ['guess' => 1]);
            $number = session('guess.number');

            if ($number === null) {
                continue;
            }

            $wrong = $number === GuessNumberController::MAX ? $number - 1 : $number + 1;

            foreach (range(1, $misses - 1) as $ignored) {
                $this->actingAs($user)->post('/games/guess/go', ['guess' => $wrong]);
            }

            $money = $user->fresh()->money;
            $this->actingAs($user)->post('/games/guess/go', ['guess' => $number]);

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

    private function guess(mixed $number): TestResponse
    {
        return $this->actingAs($this->user)->post('/games/guess/go', ['guess' => $number]);
    }
}
