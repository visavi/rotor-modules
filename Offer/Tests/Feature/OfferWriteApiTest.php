<?php

namespace Modules\Offer\Tests\Feature;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Modules\Offer\Models\Offer;
use Tests\ModuleTestCase;

class OfferWriteApiTest extends ModuleTestCase
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
        $this->overrideSetting('addofferspoint', 0);
        $this->overrideSetting('floodstime', 0);

        $this->user = User::factory()->create(['apikey' => Str::random(32)]);
    }

    public function testStoreRequiresToken(): void
    {
        $this->postJson('/api/offers', [
            'type'  => Offer::ISSUE,
            'title' => 'Тестовая проблема',
            'text'  => 'Описание тестовой проблемы',
        ])->assertStatus(400);
    }

    public function testStoreCreatesOffer(): void
    {
        $response = $this->postJson('/api/offers', [
            'type'  => Offer::ISSUE,
            'title' => 'Тестовая проблема',
            'text'  => 'Описание тестовой проблемы',
        ], $this->headers());

        $response->assertStatus(201)
            ->assertJsonPath('offer.title', 'Тестовая проблема')
            ->assertJsonPath('offer.type', Offer::ISSUE)
            ->assertJsonPath('offer.status', Offer::WAIT)
            ->assertJsonPath('offer.user.login', $this->user->login);

        $this->assertDatabaseHas('offers', [
            'title'   => 'Тестовая проблема',
            'user_id' => $this->user->id,
            'status'  => Offer::WAIT,
        ]);
    }

    public function testStoreAttachesPendingMedia(): void
    {
        $file = File::query()->create([
            'relate_id'   => 0,
            'relate_type' => Offer::$morphName,
            'path'        => '/uploads/offers/screen.jpg',
            'name'        => 'screen.jpg',
            'size'        => 1024,
            'extension'   => 'jpg',
            'mime_type'   => 'image/jpeg',
            'user_id'     => $this->user->id,
        ]);

        $id = $this->postJson('/api/offers', [
            'type'  => Offer::ISSUE,
            'title' => 'Проблема со скриншотом',
            'text'  => 'Описание проблемы со скриншотом',
        ], $this->headers())->json('offer.id');

        $this->assertSame($id, $file->fresh()->relate_id);
    }

    public function testStoreAcceptsFilesInRequest(): void
    {
        $response = $this->post('/api/offers', [
            'type'  => Offer::ISSUE,
            'title' => 'Проблема с картинкой',
            'text'  => 'Описание проблемы с картинкой',
            'files' => [UploadedFile::fake()->image('screen.jpg', 300, 300)],
        ], $this->headers());

        $response->assertStatus(201)->assertJsonCount(1, 'offer.media');
    }

    public function testStoreValidatesInput(): void
    {
        $this->postJson('/api/offers', [
            'type'  => 'unknown',
            'title' => 'Тестовая проблема',
            'text'  => 'Описание тестовой проблемы',
        ], $this->headers())->assertStatus(422)->assertJsonValidationErrors('type');

        $this->postJson('/api/offers', [
            'type'  => Offer::ISSUE,
            'title' => 'мало',
            'text'  => 'Описание тестовой проблемы',
        ], $this->headers())->assertStatus(422)->assertJsonValidationErrors('title');
    }

    public function testStoreRequiresEnoughPoints(): void
    {
        $this->overrideSetting('addofferspoint', 100);

        $this->postJson('/api/offers', [
            'type'  => Offer::ISSUE,
            'title' => 'Тестовая проблема',
            'text'  => 'Описание тестовой проблемы',
        ], $this->headers())->assertStatus(422);
    }

    public function testUpdateOwnOffer(): void
    {
        $offer = $this->createOffer($this->user->id);

        $this->patchJson('/api/offers/' . $offer->id, [
            'type'  => Offer::OFFER,
            'title' => 'Изменённый заголовок',
            'text'  => 'Изменённое описание записи',
        ], $this->headers())
            ->assertOk()
            ->assertJsonPath('offer.title', 'Изменённый заголовок')
            ->assertJsonPath('offer.type', Offer::OFFER);
    }

    public function testUpdateForeignOfferIsRejected(): void
    {
        $offer = $this->createOffer(User::factory()->create()->id);

        $this->patchJson('/api/offers/' . $offer->id, [
            'type'  => Offer::OFFER,
            'title' => 'Изменённый заголовок',
            'text'  => 'Изменённое описание записи',
        ], $this->headers())->assertStatus(404);
    }

    public function testResolvedOfferIsNotEditable(): void
    {
        $offer = $this->createOffer($this->user->id);
        $offer->update(['status' => Offer::DONE]);

        $this->patchJson('/api/offers/' . $offer->id, [
            'type'  => Offer::OFFER,
            'title' => 'Изменённый заголовок',
            'text'  => 'Изменённое описание записи',
        ], $this->headers())->assertStatus(422);
    }

    private function headers(): array
    {
        return ['Authorization' => 'Bearer ' . $this->user->apikey];
    }

    private function createOffer(int $userId): Offer
    {
        return Offer::query()->create([
            'title'      => 'Test issue',
            'text'       => 'Test issue text',
            'type'       => Offer::ISSUE,
            'status'     => Offer::WAIT,
            'user_id'    => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
