<?php

namespace Modules\Guestbook\Tests\Feature;

use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Guestbook\Models\Guestbook;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\ModuleTestCase;

#[CoversClass(Guestbook::class)]
class GuestbookControllerTest extends ModuleTestCase
{
    protected string $moduleName = 'Guestbook';

    protected function setUp(): void
    {
        parent::setUp();
        Relation::morphMap([Guestbook::$morphName => Guestbook::class]);
    }

    public function testGuestbook(): void
    {
        Guestbook::unsetEventDispatcher();

        $guest = Guestbook::factory()->create([
            'user_id'    => 1,
            'text'       => 'Test text message',
            'ip'         => '127.0.0.1',
            'brow'       => 'Chrome 60.0',
            'created_at' => now()->timestamp,
        ]);

        $getGuest = $guest->fresh();
        self::assertSame('Test text message', $getGuest->text);

        $guest->update(['text' => 'Test simple message']);

        $getGuest = $guest->fresh();
        self::assertSame('Test simple message', $getGuest->text);

        $guest->delete();

        $getGuest = $guest->fresh();
        self::assertNull($getGuest);
    }
}
