<?php

namespace Modules\Guestbook\Tests\Feature;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Guestbook\Models\Guestbook;
use Tests\ModuleTestCase;

class GuestbookFormTest extends ModuleTestCase
{
    protected string $moduleName = 'Guestbook';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([Guestbook::$morphName => Guestbook::class]);

        $this->overrideSetting('guestbook_text_max', 1000);
        $this->overrideSetting('bookadds', 1);
        $this->overrideSetting('captcha_type', 'graphical');

        $this->user = User::factory()->create();
    }

    public function testFormIsCompactForUser(): void
    {
        $this->actingAs($this->user)
            ->get('/guestbook')
            ->assertOk()
            ->assertSee('data-compact', false);
    }

    public function testFormIsCompactForGuest(): void
    {
        $this->get('/guestbook')
            ->assertOk()
            ->assertSee('data-compact', false);
    }

    public function testFormIsOpenWithPendingFiles(): void
    {
        File::query()->create([
            'relate_id'   => 0,
            'relate_type' => Guestbook::$morphName,
            'path'        => '/uploads/guestbook/screen.jpg',
            'name'        => 'screen.jpg',
            'size'        => 1024,
            'extension'   => 'jpg',
            'mime_type'   => 'image/jpeg',
            'user_id'     => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->get('/guestbook')
            ->assertOk()
            ->assertDontSee('data-compact', false);
    }

    public function testFormIsOpenAfterFailedSubmit(): void
    {
        $this->withSession(['_old_input' => ['msg' => 'Недописанное сообщение']])
            ->get('/guestbook')
            ->assertOk()
            ->assertDontSee('data-compact', false);
    }
}
