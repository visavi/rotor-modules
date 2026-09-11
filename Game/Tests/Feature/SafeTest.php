<?php

namespace Modules\Game\Tests\Feature;

use App\Models\User;
use Modules\Game\Http\Controllers\SafeController;
use Tests\ModuleTestCase;

class SafeTest extends ModuleTestCase
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
        $this->get('/games/safe')->assertForbidden();
    }

    public function testPageIsOpen(): void
    {
        $this->actingAs($this->user)->get('/games/safe')->assertOk();
    }

    public function testFirstTryChargesOncePerGame(): void
    {
        $this->guess([0, 0, 0, 0, 0]);
        $this->assertSame(5000 - SafeController::PRICE, $this->user->fresh()->money);

        // Вторая попытка той же партии бесплатна
        $this->guess([1, 1, 1, 1, 1]);
        $this->assertSame(5000 - SafeController::PRICE, $this->user->fresh()->money);
    }

    public function testGameIsRejectedWithoutMoney(): void
    {
        $this->user->update(['money' => 10]);

        $this->guess([0, 0, 0, 0, 0])->assertSessionHasErrors('code');

        $this->assertSame(10, $this->user->fresh()->money);
        $this->assertNull(session('safe'));
    }

    public function testNonDigitsAreRejected(): void
    {
        $this->actingAs($this->user)
            ->post('/games/safe/go', [
                'code0' => 'a', 'code1' => 1, 'code2' => 2, 'code3' => 3, 'code4' => 4,
            ])
            ->assertSessionHasErrors('code');

        // Прежде «a» превращалась в ноль и попытка сгорала
        $this->assertSame(5000, $this->user->fresh()->money);
    }

    public function testOpenedSafePaysPrize(): void
    {
        $this->guess([0, 0, 0, 0, 0]);

        $cipher = session('safe.cipher');
        $money = $this->user->fresh()->money;

        $response = $this->guess($cipher);

        $this->assertSame('opened', $response->original->getData()['game']['result']);
        $this->assertSame($money + SafeController::PRIZE, $this->user->fresh()->money);
        $this->assertNull(session('safe'), 'Партия не закончилась');
    }

    public function testTriesRunOutAndCipherIsRevealed(): void
    {
        $this->guess([0, 0, 0, 0, 0]);

        $cipher = session('safe.cipher');
        $wrong = array_map(static fn (int $digit) => ($digit + 1) % 10, $cipher);

        $response = null;
        for ($i = 1; $i < SafeController::TRIES; $i++) {
            $response = $this->guess($wrong);
        }

        $game = $response->original->getData()['game'];

        $this->assertSame('failed', $game['result']);
        $this->assertSame($cipher, $game['cipher'], 'Шифр не показан после провала');
        $this->assertNull(session('safe'));
    }

    public function testCipherIsHiddenWhileGameRuns(): void
    {
        $this->guess([0, 0, 0, 0, 0]);

        $game = $this->actingAs($this->user)->get('/games/safe')->original->getData()['game'];

        // Незаконченная партия не отдаёт шифр в шаблон: подсмотреть нечего
        $this->assertNull($game['cipher']);
        $this->assertSame(SafeController::TRIES - 1, $game['try']);
    }

    public function testMarksDoNotLeakPosition(): void
    {
        $this->guess([0, 0, 0, 0, 0]);

        $cipher = session('safe.cipher');

        // Цифра шифра, поставленная на чужое место, даёт звёздочку и только
        $position = null;
        foreach ($cipher as $index => $digit) {
            if ($cipher[0] !== $digit) {
                $position = $index;
                break;
            }
        }

        if ($position === null) {
            $this->markTestSkipped('Шифр из одинаковых цифр');
        }

        // Заполнитель берём вне шифра, иначе он сам даёт звёздочку и тест плавает
        $filler = null;
        foreach (range(0, 9) as $digit) {
            if (! in_array($digit, $cipher, true)) {
                $filler = $digit;
                break;
            }
        }

        $codes = array_fill(0, SafeController::LENGTH, $filler);
        $codes[0] = $cipher[$position];

        $marks = $this->guess($codes)->original->getData()['game']['marks'];

        if ($cipher[0] === $codes[0]) {
            $this->markTestSkipped('Цифра случайно попала на своё место');
        }

        $this->assertSame(SafeController::MOVED, $marks[0]);
        $this->assertSame(SafeController::ABSENT, $marks[$position], 'Метка выдала позицию цифры');
    }

    public function testHistoryKeepsEveryTry(): void
    {
        $this->guess([0, 0, 0, 0, 0]);
        $response = $this->guess([1, 1, 1, 1, 1]);

        $history = $response->original->getData()['game']['history'];

        $this->assertCount(2, $history);
        $this->assertSame([0, 0, 0, 0, 0], $history[0]['codes']);
        $this->assertSame([1, 1, 1, 1, 1], $history[1]['codes']);
    }

    public function testAjaxTryReturnsSafeOnly(): void
    {
        $response = $this->actingAs($this->user)->postJson('/games/safe/go', [
            'code0' => 0, 'code1' => 0, 'code2' => 0, 'code3' => 0, 'code4' => 0,
        ], ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertOk()->assertJson(['success' => true]);

        $html = $response->json('html');

        $this->assertStringContainsString('id="safe-box"', $html);
        $this->assertStringNotContainsString('<html', $html);
    }

    /**
     * Отправляет код
     */
    private function guess(array $codes): \Illuminate\Testing\TestResponse
    {
        $input = [];

        foreach ($codes as $position => $digit) {
            $input['code' . $position] = $digit;
        }

        return $this->actingAs($this->user)->post('/games/safe/go', $input);
    }
}
