<?php

namespace Modules\Load\Tests\Feature;

use App\Models\File;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Load\Models\Down;
use Modules\Load\Models\Load;
use Modules\Load\Services\ZipMap;
use Tests\ModuleTestCase;
use ZipArchive;

class ZipViewTest extends ModuleTestCase
{
    protected string $moduleName = 'Load';

    private Down $down;

    private File $file;

    protected function setUp(): void
    {
        parent::setUp();

        Relation::morphMap([Down::$morphName => Down::class]);

        $load = Load::query()->create(['name' => 'Категория']);

        $this->down = Down::query()->create([
            'category_id' => $load->id,
            'title'       => 'Загрузка',
            'text'        => 'Описание',
            'user_id'     => User::factory()->create()->id,
            'active'      => true,
            'created_at'  => now(),
        ]);

        $this->file = $this->attachArchive();
    }

    protected function tearDown(): void
    {
        $path = public_path($this->file->path);

        if (file_exists($path)) {
            unlink($path);
        }

        parent::tearDown();
    }

    public function testArchiveIsListed(): void
    {
        $this->get($this->zipUrl())
            ->assertOk()
            ->assertSee('readme.txt');
    }

    public function testMapIsCachedAfterFirstView(): void
    {
        // Пока карты нет, о содержимом архива ничего не известно
        $this->assertNull(ZipMap::has($this->file, 0));

        $this->get($this->zipUrl())->assertOk();

        $this->assertTrue(ZipMap::has($this->file, 0));
    }

    public function testTextFileIsShown(): void
    {
        $this->get($this->zipUrl(0))
            ->assertOk()
            ->assertSee('Привет');
    }

    public function testImageIsCacheable(): void
    {
        $response = $this->get($this->zipUrl(1))->assertOk();

        // Картинка из архива не меняется — её кеширует браузер
        $this->assertSame('image/gif', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('max-age=86400', $response->headers->get('Cache-Control'));
        $this->assertNotEmpty($response->headers->get('ETag'));
    }

    public function testUnknownIndexIsRejectedWithoutOpeningArchive(): void
    {
        // Прогреваем карту, дальше перебор отбивается по ней
        $this->get($this->zipUrl())->assertOk();

        // Файл убираем с диска: если бы архив открывался, ответ был бы другим
        unlink(public_path($this->file->path));

        $this->get($this->zipUrl(500))
            ->assertOk()
            ->assertSee(__('load::loads.file_not_read'));
    }

    public function testMapIsForgottenWithFile(): void
    {
        $this->get($this->zipUrl())->assertOk();
        $this->assertTrue(ZipMap::has($this->file, 0));

        // Карту убирает наблюдатель за файлами
        $this->file->delete();

        $this->assertNull(ZipMap::has($this->file, 0));
    }

    public function testMapKnowsItsEntries(): void
    {
        $this->get($this->zipUrl())->assertOk();

        $this->assertTrue(ZipMap::has($this->file, 0));
        $this->assertFalse(ZipMap::has($this->file, 500));
    }

    private function zipUrl(?int $zid = null): string
    {
        $url = '/downs/' . $this->down->id . '/zip/' . $this->file->id;

        return $zid === null ? $url : $url . '/' . $zid;
    }

    private function attachArchive(): File
    {
        $name = 'test-' . uniqid() . '.zip';
        $path = public_path('uploads/files/' . $name);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $archive = new ZipArchive();
        $archive->open($path, ZipArchive::CREATE);
        $archive->addFromString('readme.txt', 'Привет из архива');
        $archive->addFromString('logo.gif', base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'));
        $archive->close();

        return File::query()->create([
            'relate_id'   => $this->down->id,
            'relate_type' => Down::$morphName,
            'path'        => 'uploads/files/' . $name,
            'name'        => 'archive.zip',
            'extension'   => 'zip',
            'mime_type'   => 'application/zip',
            'size'        => filesize($path),
            'user_id'     => $this->down->user_id,
            'created_at'  => now(),
        ]);
    }
}
