<?php

namespace Modules\Offer\Tests\Feature;

use App\Classes\Registry;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Modules\Offer\Models\Offer;
use Tests\ModuleTestCase;

class OfferApiTest extends ModuleTestCase
{
    protected string $moduleName = 'Offer';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([Offer::$morphName => Offer::class]);

        // Типы регистрирует ModuleServiceProvider, в тестах модуль поднимается вручную
        Registry::ratingType(Offer::$morphName);

        $this->user = User::factory()->create();
    }

    public function testViewIsAvailableForGuests(): void
    {
        $offer = $this->createOffer();

        $response = $this->getJson('/api/offers/' . $offer->id);

        $response->assertOk();
        $response->assertJsonPath('offer.id', $offer->id);
        $response->assertJsonPath('offer.type', Offer::ISSUE);
        $response->assertJsonPath('offer.status', Offer::WAIT);
        $response->assertJsonPath('offer.title', 'Test issue');
        $response->assertJsonPath('offer.url', $offer->getViewUrl());
        $response->assertJsonPath('offer.user.login', $this->user->login);
        $response->assertJsonPath('offer.breadcrumbs.0.title', __('offer::offers.section'));
        // Голос не проставлен, но цель голосования гость видит
        $response->assertJsonPath('offer.vote.value', null);
        $response->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function testViewReturnsCommentsPaginated(): void
    {
        $offer = $this->createOffer();

        $first = $this->addComment($offer, 'First comment');
        $this->addComment($offer, 'Second comment', $first->id);

        $response = $this->getJson('/api/offers/' . $offer->id . '?per_page=1');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('meta.total', 2);
        $response->assertJsonPath('data.0.parent_id', null);
        $this->assertStringContainsString('First comment', $response->json('data.0.text'));

        // Ответ лежит на второй странице и знает своего родителя
        $this->getJson('/api/offers/' . $offer->id . '?per_page=1&page=2')
            ->assertOk()
            ->assertJsonPath('data.0.parent_id', $first->id);
    }

    public function testDeletedCommentStaysAsPlaceholder(): void
    {
        $offer = $this->createOffer();
        $comment = $this->addComment($offer, 'Comment to delete');
        $comment->softDelete();

        $response = $this->getJson('/api/offers/' . $offer->id);

        $response->assertOk();
        $response->assertJsonPath('data.0.deleted', true);
        $response->assertJsonPath('data.0.text', null);
        $response->assertJsonPath('data.0.user', null);
    }

    public function testViewReturnsVoteOfCurrentUser(): void
    {
        $offer = $this->createOffer();
        $voter = User::factory()->create(['apikey' => Str::random(32)]);

        $this->postJson('/api/rating', [
            'type' => Offer::$morphName,
            'id'   => $offer->id,
            'vote' => '+',
        ], ['Authorization' => 'Bearer ' . $voter->apikey])->assertOk();

        $this->getJson('/api/offers/' . $offer->id, ['Authorization' => 'Bearer ' . $voter->apikey])
            ->assertOk()
            ->assertJsonPath('offer.vote.value', '+')
            ->assertJsonPath('offer.vote.own', false)
            ->assertJsonPath('offer.rating', 2);
    }

    public function testIndexFiltersByType(): void
    {
        $this->createOffer();

        Offer::query()->create([
            'type'       => Offer::OFFER,
            'title'      => 'Test offer',
            'text'       => 'Test offer text',
            'user_id'    => $this->user->id,
            'rating'     => 1,
            'status'     => Offer::WAIT,
            'created_at' => now(),
        ]);

        $this->getJson('/api/offers?type=issue')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', Offer::ISSUE);

        // Без параметра приходят предложения
        $this->getJson('/api/offers')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', Offer::OFFER);
    }

    public function testViewNotFound(): void
    {
        $this->getJson('/api/offers/100')->assertStatus(404);
    }

    private function createOffer(): Offer
    {
        return Offer::query()->create([
            'type'       => Offer::ISSUE,
            'title'      => 'Test issue',
            'text'       => 'Test issue text',
            'user_id'    => $this->user->id,
            'rating'     => 1,
            'status'     => Offer::WAIT,
            'created_at' => now(),
        ]);
    }

    private function addComment(Offer $offer, string $text, ?int $parentId = null): Comment
    {
        return Comment::query()->create([
            'relate_type' => Offer::$morphName,
            'relate_id'   => $offer->id,
            'parent_id'   => $parentId,
            'text'        => $text,
            'user_id'     => $this->user->id,
            'created_at'  => now(),
            'ip'          => '127.0.0.1',
            'brow'        => 'test',
        ]);
    }
}
