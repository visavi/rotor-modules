<?php

namespace Modules\Photo\Tests\Feature;

use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Photo\Models\Photo;
use Tests\ModuleTestCase;

class PhotoSmokeTest extends ModuleTestCase
{
    protected string $moduleName = 'Photo';

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([Photo::$morphName => Photo::class]);
    }

    public function testNewComments(): void
    {
        $this->assertSame(url('/photos/comments'), route('photos.new-comments'));
        $this->get(route('photos.new-comments'))->assertOk();
    }
}
