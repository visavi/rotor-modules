<?php

namespace Modules\Transfer\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Transfer\Services\TransferService;
use Tests\ModuleTestCase;

class TransferTest extends ModuleTestCase
{
    protected string $moduleName = 'Transfer';

    private User $user;

    private User $recipient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->overrideSetting('sendmoneypoint', 0);
        $this->overrideSetting('comment_text_max', 1000);

        $this->user = User::factory()->create(['money' => 500, 'point' => 100]);
        $this->recipient = User::factory()->create(['money' => 0]);
    }

    public function testFormForRecipientOpens(): void
    {
        $this->actingAs($this->user)
            ->get('/transfers?user=' . $this->recipient->login)
            ->assertOk()
            ->assertSee('value="' . $this->recipient->login . '"', false);
    }

    public function testMoneyIsTransferred(): void
    {
        $this->actingAs($this->user)
            ->post('/transfers/send', [
                'user'  => $this->recipient->login,
                'money' => 100,
                'text'  => 'За помощь',
            ])
            ->assertRedirect('/transfers')
            ->assertSessionHas('success');

        $this->assertSame(400, (int) $this->user->fresh()->money);
        $this->assertSame(100, (int) $this->recipient->fresh()->money);
        $this->assertDatabaseHas('transfers', [
            'user_id'      => $this->user->id,
            'recipient_id' => $this->recipient->id,
            'text'         => 'За помощь',
            'total'        => 100,
        ]);
    }

    public function testErrorReturnsToRecipientForm(): void
    {
        $from = '/transfers?user=' . $this->recipient->login;

        $this->actingAs($this->user)
            ->from($from)
            ->post('/transfers/send', ['user' => $this->recipient->login, 'money' => 1000])
            ->assertRedirect($from)
            ->assertSessionHasErrors('money');

        $this->assertSame(500, (int) $this->user->fresh()->money);
    }

    public function testNonNumericAmountIsRejected(): void
    {
        $this->actingAs($this->user)
            ->post('/transfers/send', ['user' => $this->recipient->login, 'money' => 'abc'])
            ->assertSessionHasErrors('money');
    }

    public function testTransferToYourselfIsRejected(): void
    {
        $this->actingAs($this->user)
            ->post('/transfers/send', ['user' => $this->user->login, 'money' => 100])
            ->assertSessionHasErrors('user');

        $this->assertSame(500, (int) $this->user->fresh()->money);
    }

    public function testPointsThresholdIsChecked(): void
    {
        $this->overrideSetting('sendmoneypoint', 1000);

        $this->actingAs($this->user)
            ->post('/transfers/send', ['user' => $this->recipient->login, 'money' => 100])
            ->assertSessionHasErrors('money');

        $this->assertSame(500, (int) $this->user->fresh()->money);
    }

    public function testMarkupIsNotCountedInLength(): void
    {
        $this->actingAs($this->user)
            ->post('/transfers/send', [
                'user'  => $this->recipient->login,
                'money' => 100,
                'text'  => '<p>' . Str::repeat('a', 1000) . '</p>',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($this->user)
            ->post('/transfers/send', [
                'user'  => $this->recipient->login,
                'money' => 100,
                'text'  => Str::repeat('a', 1001),
            ])
            ->assertSessionHasErrors('text');
    }

    public function testStaleBalanceDoesNotGoNegative(): void
    {
        // Второй запрос прошёл проверку баланса, пока первый уже списал деньги
        $stale = $this->user->fresh();
        $this->user->update(['money' => 50]);

        try {
            app(TransferService::class)->send($stale, $this->recipient, 100, 'Гонка');
            $this->fail('Перевод сверх баланса должен быть отклонён');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('money', $e->errors());
        }

        $this->assertSame(50, (int) $this->user->fresh()->money);
        $this->assertSame(0, (int) $this->recipient->fresh()->money);
        $this->assertDatabaseMissing('transfers', ['user_id' => $this->user->id]);
    }

    public function testGuestCannotTransfer(): void
    {
        $this->post('/transfers/send', ['user' => $this->recipient->login, 'money' => 100])
            ->assertForbidden();
    }
}
