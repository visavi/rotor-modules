<?php

namespace Modules\Offer\Tests\Feature;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Offer\Models\Offer;
use Tests\ModuleTestCase;

class OfferSmokeTest extends ModuleTestCase
{
    protected string $moduleName = 'Offer';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([Offer::$morphName => Offer::class]);

        $this->overrideSetting('offer_title_min', 3);
        $this->overrideSetting('offer_title_max', 50);
        $this->overrideSetting('offer_text_min', 5);
        $this->overrideSetting('offer_text_max', 1000);
        $this->overrideSetting('addofferspoint', 50);
        $this->overrideSetting('postoffers', 10);

        $this->user = User::factory()->create(['point' => 100]);
    }

    public function testIndex(): void
    {
        $this->get(route('offers.index'))->assertOk();
    }

    public function testView(): void
    {
        $offer = $this->createOffer();

        $this->get($offer->getViewUrl())->assertOk();
    }

    public function testCreateFormShowsUploader(): void
    {
        $this->actingAs($this->user)
            ->get(route('offers.create', ['type' => Offer::ISSUE]))
            ->assertOk()
            ->assertSee('js-files', false);
    }

    public function testCreateAttachesUploadedFiles(): void
    {
        // Файл загружается до создания записи и висит с relate_id = 0
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

        $this->actingAs($this->user)->post(route('offers.create'), [
            'type'  => Offer::ISSUE,
            'title' => 'Test issue',
            'text'  => 'Test issue text',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $offer = Offer::query()->where('title', 'Test issue')->firstOrFail();

        $this->assertSame($offer->id, $file->fresh()->relate_id);
        $this->assertCount(1, $offer->getMedia());
    }

    public function testDeleteRemovesFiles(): void
    {
        $offer = $this->createOffer();

        File::query()->create([
            'relate_id'   => $offer->id,
            'relate_type' => Offer::$morphName,
            'path'        => '/uploads/offers/screen.jpg',
            'name'        => 'screen.jpg',
            'size'        => 1024,
            'extension'   => 'jpg',
            'mime_type'   => 'image/jpeg',
            'user_id'     => $this->user->id,
        ]);

        $offer->delete();

        $this->assertDatabaseMissing('files', [
            'relate_type' => Offer::$morphName,
            'relate_id'   => $offer->id,
        ]);
    }

    private function createOffer(): Offer
    {
        return Offer::query()->create([
            'type'       => Offer::ISSUE,
            'title'      => 'Test offer',
            'text'       => 'Test offer text',
            'user_id'    => $this->user->id,
            'rating'     => 1,
            'status'     => Offer::WAIT,
            'created_at' => now(),
        ]);
    }
}
