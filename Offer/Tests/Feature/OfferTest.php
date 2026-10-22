<?php

namespace Modules\Offer\Tests\Feature;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Offer\Models\Offer;
use Tests\ModuleTestCase;

class OfferTest extends ModuleTestCase
{
    protected string $moduleName = 'Offer';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([Offer::$morphName => Offer::class]);

        $this->overrideSetting('offer_title_min', 5);
        $this->overrideSetting('offer_title_max', 50);
        $this->overrideSetting('offer_text_min', 10);
        $this->overrideSetting('offer_text_max', 1000);
        $this->overrideSetting('offer_reply_min', 5);
        $this->overrideSetting('offer_reply_max', 1000);
        $this->overrideSetting('addofferspoint', 50);
        $this->overrideSetting('postoffers', 10);
        $this->overrideSetting('floodstime', 0);

        $this->user = User::factory()->create(['point' => 100]);
    }

    public function testOfferIsCreated(): void
    {
        $this->actingAs($this->user)
            ->post('/offers/create', [
                'type'  => Offer::OFFER,
                'title' => 'Новая идея',
                'text'  => '<p>Описание новой идеи</p>',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('offers', [
            'user_id' => $this->user->id,
            'type'    => Offer::OFFER,
            'title'   => 'Новая идея',
            'status'  => Offer::WAIT,
        ]);
    }

    public function testMarkupIsNotCountedInLength(): void
    {
        $this->actingAs($this->user)
            ->post('/offers/create', [
                'type'  => Offer::OFFER,
                'title' => 'Новая идея',
                'text'  => '<p><strong><em>Коротко</em></strong></p>',
            ])
            ->assertSessionHasErrors('text');

        $this->assertDatabaseCount('offers', 0);
    }

    public function testUnknownTypeIsRejected(): void
    {
        $this->actingAs($this->user)
            ->post('/offers/create', [
                'type'  => 'unknown',
                'title' => 'Новая идея',
                'text'  => 'Описание новой идеи',
            ])
            ->assertSessionHasErrors('type');

        $this->assertDatabaseCount('offers', 0);
    }

    public function testFloodErrorIsShownUnderText(): void
    {
        $this->overrideSetting('floodstime', 60);

        $data = ['type' => Offer::OFFER, 'title' => 'Новая идея', 'text' => 'Описание новой идеи'];

        $this->actingAs($this->user)->post('/offers/create', $data)->assertSessionHasNoErrors();
        $this->actingAs($this->user)->post('/offers/create', $data)->assertSessionHasErrors('text');

        $this->assertDatabaseCount('offers', 1);
    }

    public function testNotEnoughPoints(): void
    {
        $this->user->update(['point' => 10]);

        $this->actingAs($this->user)
            ->post('/offers/create', [
                'type'  => Offer::OFFER,
                'title' => 'Новая идея',
                'text'  => 'Описание новой идеи',
            ])
            ->assertSessionHasErrors('point');

        $this->assertDatabaseCount('offers', 0);
    }

    public function testGuestCannotCreate(): void
    {
        $this->get('/offers/create')->assertForbidden();
        $this->post('/offers/create', ['type' => Offer::OFFER])->assertForbidden();
    }

    public function testOwnOfferIsEdited(): void
    {
        $offer = $this->createOffer();

        $this->actingAs($this->user)
            ->get('/offers/' . $offer->id . '/edit')
            ->assertOk()
            ->assertSee('Описание записи');

        $this->actingAs($this->user)
            ->post('/offers/' . $offer->id . '/edit', [
                'type'  => Offer::ISSUE,
                'title' => 'Новый заголовок',
                'text'  => 'Новое описание записи',
            ])
            ->assertRedirect(route('offers.view', ['id' => $offer->id]));

        $offer->refresh();
        $this->assertSame(Offer::ISSUE, $offer->type);
        $this->assertSame('Новый заголовок', $offer->title);
    }

    public function testResolvedOfferIsNotEdited(): void
    {
        $offer = $this->createOffer(['status' => Offer::DONE]);

        $this->actingAs($this->user)
            ->post('/offers/' . $offer->id . '/edit', [
                'type'  => Offer::ISSUE,
                'title' => 'Новый заголовок',
                'text'  => 'Новое описание записи',
            ])
            ->assertSee(__('offer::offers.already_resolved'));

        $this->assertSame('Заголовок записи', $offer->fresh()->title);
    }

    public function testForeignOfferIsNotEdited(): void
    {
        $offer = $this->createOffer(['user_id' => User::factory()->create()->id]);

        $this->actingAs($this->user)
            ->get('/offers/' . $offer->id . '/edit')
            ->assertNotFound();
    }

    public function testAdminEditsOfferAndClosesComments(): void
    {
        $offer = $this->createOffer();

        $this->actingAs($this->admin())
            ->post('/admin/offers/' . $offer->id . '/edit', [
                'type'   => Offer::OFFER,
                'title'  => 'Правка администрации',
                'text'   => 'Описание после правки',
                'closed' => 1,
            ])
            ->assertRedirect(route('admin.offers.view', ['id' => $offer->id]));

        $offer->refresh();
        $this->assertSame('Правка администрации', $offer->title);
        $this->assertTrue($offer->closed);
    }

    public function testAdminReplies(): void
    {
        $offer = $this->createOffer();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/admin/offers/' . $offer->id . '/reply')
            ->assertOk();

        $this->actingAs($admin)
            ->post('/admin/offers/' . $offer->id . '/reply', ['reply' => '<p><b>Ок</b></p>', 'status' => Offer::DONE])
            ->assertSessionHasErrors('reply');

        $this->actingAs($admin)
            ->post('/admin/offers/' . $offer->id . '/reply', ['reply' => 'Уже исправили', 'status' => 'unknown'])
            ->assertSessionHasErrors('status');

        $this->actingAs($admin)
            ->post('/admin/offers/' . $offer->id . '/reply', ['reply' => 'Уже исправили', 'status' => Offer::DONE])
            ->assertSessionHas('success');

        $offer->refresh();
        $this->assertSame(Offer::DONE, $offer->status);
        $this->assertSame($admin->id, $offer->reply_user_id);
    }

    public function testAdminDeletesOfferFromItsPage(): void
    {
        $offer = $this->createOffer();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get('/admin/offers/' . $offer->id)
            ->assertSee(route('admin.offers.delete', ['type' => Offer::OFFER]), false)
            ->assertSee('name="del[]" value="' . $offer->id . '"', false);

        $this->actingAs($admin)
            ->post('/admin/offers/delete', ['del' => []])
            ->assertSessionHasErrors('del');

        $this->actingAs($admin)
            ->post('/admin/offers/delete?type=offer', ['del' => [$offer->id]])
            ->assertRedirect(route('admin.offers.index', ['type' => Offer::OFFER, 'page' => 1]));

        $this->assertNull($offer->fresh());
    }

    public function testOnlyBossRecounts(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/offers/restatement')
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['level' => User::BOSS]))
            ->post('/admin/offers/restatement')
            ->assertSessionHas('success');
    }

    private function admin(): User
    {
        return User::factory()->create(['level' => User::ADMIN]);
    }

    private function createOffer(array $attributes = []): Offer
    {
        return Offer::factory()->create($attributes + [
            'type'       => Offer::OFFER,
            'title'      => 'Заголовок записи',
            'text'       => 'Описание записи',
            'user_id'    => $this->user->id,
            'rating'     => 1,
            'status'     => Offer::WAIT,
            'created_at' => now(),
        ]);
    }
}
