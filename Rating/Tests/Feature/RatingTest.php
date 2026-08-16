<?php

namespace Modules\Rating\Tests\Feature;

use App\Models\User;
use Modules\Rating\Models\Rating;
use Tests\ModuleTestCase;

class RatingTest extends ModuleTestCase
{
    protected string $moduleName = 'Rating';

    private User $user;

    private User $recipient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->overrideSetting('editratingpoint', 10);
        $this->overrideSetting('ratinglist', 10);

        $this->user = User::factory()->create(['point' => 100, 'rating' => 5, 'posrating' => 5]);
        $this->recipient = User::factory()->create(['rating' => 0, 'posrating' => 0, 'negrating' => 0]);
    }

    public function testPlusRaisesReputation(): void
    {
        $this->actingAs($this->user)
            ->post('/users/' . $this->recipient->login . '/rating?vote=plus', ['text' => 'Хороший человек'])
            ->assertRedirect('users/' . $this->recipient->login);

        $recipient = $this->recipient->fresh();

        $this->assertSame(1, $recipient->posrating);
        $this->assertSame(1, $recipient->rating);
        $this->assertDatabaseHas('rating', [
            'user_id'      => $this->user->id,
            'recipient_id' => $this->recipient->id,
            'vote'         => '+',
        ]);

        // Получатель узнаёт об оценке из привата
        $this->assertSame(1, $recipient->getCountMessages());
    }

    public function testMinusLowersReputation(): void
    {
        $this->actingAs($this->user)
            ->post('/users/' . $this->recipient->login . '/rating?vote=minus', ['text' => 'Нарушает правила'])
            ->assertRedirect('users/' . $this->recipient->login);

        $recipient = $this->recipient->fresh();

        $this->assertSame(1, $recipient->negrating);
        $this->assertSame(-1, $recipient->rating);
    }

    public function testMinusNeedsOwnReputation(): void
    {
        $this->user->update(['rating' => 0]);

        $this->actingAs($this->user)
            ->post('/users/' . $this->recipient->login . '/rating?vote=minus', ['text' => 'Нарушает правила'])
            ->assertSessionHasErrors();

        $this->assertDatabaseCount('rating', 0);
    }

    public function testShortTextIsRejected(): void
    {
        $this->actingAs($this->user)
            ->post('/users/' . $this->recipient->login . '/rating?vote=plus', ['text' => 'Ок'])
            ->assertSessionHasErrors('text');

        $this->assertDatabaseCount('rating', 0);
    }

    public function testVoteNeedsPoints(): void
    {
        $this->user->update(['point' => 1]);

        $this->actingAs($this->user)
            ->post('/users/' . $this->recipient->login . '/rating?vote=plus', ['text' => 'Хороший человек'])
            ->assertSee(__('rating::ratings.reputation_point', ['point' => plural(10, setting('scorename'))]));

        $this->assertDatabaseCount('rating', 0);
    }

    public function testSecondVoteIsBlockedForThreeMonths(): void
    {
        $this->vote();

        $this->actingAs($this->user)
            ->post('/users/' . $this->recipient->login . '/rating?vote=plus', ['text' => 'И ещё раз'])
            ->assertSee(__('rating::ratings.reputation_already_changed'));

        $this->assertDatabaseCount('rating', 1);
    }

    public function testVoteIsAllowedAgainAfterThreeMonths(): void
    {
        $this->vote(['created_at' => now()->subMonths(4)]);

        $this->actingAs($this->user)
            ->post('/users/' . $this->recipient->login . '/rating?vote=plus', ['text' => 'Прошло много времени'])
            ->assertRedirect('users/' . $this->recipient->login);

        $this->assertDatabaseCount('rating', 2);
    }

    public function testVoteForYourselfIsRejected(): void
    {
        $this->actingAs($this->user)
            ->post('/users/' . $this->user->login . '/rating?vote=plus', ['text' => 'Сам себе хорош'])
            ->assertSee(__('rating::ratings.reputation_yourself'));

        $this->assertDatabaseCount('rating', 0);
    }

    public function testUnknownUserIsNotFound(): void
    {
        $this->actingAs($this->user)
            ->get('/users/nobody/rating?vote=plus')
            ->assertNotFound();
    }

    public function testGuestIsRejected(): void
    {
        $this->get('/users/' . $this->recipient->login . '/rating?vote=plus')
            ->assertForbidden();
    }

    public function testHistoryPagesAreOpen(): void
    {
        $this->vote();

        $this->actingAs($this->user)
            ->get('/ratings/' . $this->recipient->login)
            ->assertOk()
            ->assertSee('Хороший человек');

        $this->actingAs($this->user)
            ->get('/ratings/' . $this->user->login . '/gave')
            ->assertOk()
            ->assertSee('Хороший человек');
    }

    public function testOnlyAdminDeletesRecord(): void
    {
        $rating = $this->vote();

        $this->actingAs($this->user)
            ->postJson('/ratings/delete', ['id' => $rating->id], $this->ajax())
            ->assertOk()
            ->assertJsonPath('success', false);

        $admin = User::factory()->create(['level' => User::ADMIN]);

        $this->actingAs($admin)
            ->postJson('/ratings/delete', ['id' => $rating->id], $this->ajax())
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('rating', 0);
    }

    public function testVotesAreRemovedWithUser(): void
    {
        $this->vote();

        // Голоса чистит хук onDeleteUser
        $this->recipient->delete();

        $this->assertDatabaseCount('rating', 0);
    }

    /**
     * Удаление идёт только запросом из браузера
     */
    private function ajax(): array
    {
        return ['X-Requested-With' => 'XMLHttpRequest'];
    }

    private function vote(array $attributes = []): Rating
    {
        return Rating::query()->create([
            'user_id'      => $this->user->id,
            'recipient_id' => $this->recipient->id,
            'text'         => 'Хороший человек',
            'vote'         => '+',
            ...$attributes,
        ]);
    }
}
