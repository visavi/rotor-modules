<?php

namespace Modules\PageEditor\Tests\Feature;

use Modules\PageEditor\Support\PathResolver;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\ModuleTestCase;

class PathResolverTest extends ModuleTestCase
{
    protected string $moduleName = 'PageEditor';

    public function testNormalizeStripsDotSegments(): void
    {
        $this->assertSame('a/b', PathResolver::normalize('./a//b/'));
        $this->assertSame('b', PathResolver::normalize('a/../b'));
    }

    public function testNormalizeCannotEscapeRoot(): void
    {
        $this->assertSame('', PathResolver::normalize('../../..'));
        $this->assertSame('etc/passwd', PathResolver::normalize('../../etc/passwd'));
    }

    /**
     * ?path=a%00b доходил до is_file()/filesize() как есть и давал ValueError → 500
     */
    public function testNormalizeStripsNullBytes(): void
    {
        $this->assertSame('ab', PathResolver::normalize("a\0b"));
        $this->assertSame('a/b', PathResolver::normalize("a/\0b"));
    }

    public function testResolveBuildsPathInsideRoot(): void
    {
        $this->assertSame(resource_path('views'), PathResolver::resolve('views'));
        $this->assertSame(resource_path('views/themes'), PathResolver::resolve('views', 'themes'));
    }

    public function testResolveRejectsUnknownRoot(): void
    {
        $this->expectException(NotFoundHttpException::class);

        PathResolver::resolve('vendor', '');
    }

    public function testResolveKeepsSymlinkedModulePath(): void
    {
        $path = PathResolver::resolve('modules', 'PageEditor/module.php');

        $this->assertSame(base_path('modules/PageEditor/module.php'), $path);
        $this->assertFileExists($path);
    }

    public function testExtensionHandlesBladeAndPlainFiles(): void
    {
        $this->assertSame('blade.php', PathResolver::extension('index.blade.php'));
        $this->assertSame('php', PathResolver::extension('module.php'));
        $this->assertSame('json', PathResolver::extension('main.json'));
        $this->assertSame('', PathResolver::extension('Makefile'));
    }

    public function testIsEditableFollowsConfig(): void
    {
        $this->assertTrue(PathResolver::isEditable('index.blade.php'));
        $this->assertTrue(PathResolver::isEditable('app.scss'));
        $this->assertFalse(PathResolver::isEditable('logo.png'));
    }
}
