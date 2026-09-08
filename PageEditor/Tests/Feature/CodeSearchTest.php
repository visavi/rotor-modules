<?php

namespace Modules\PageEditor\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\File;
use Modules\PageEditor\Support\CodeSearcher;
use Tests\ModuleTestCase;

class CodeSearchTest extends ModuleTestCase
{
    protected string $moduleName = 'PageEditor';

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = resource_path('views/page_editor_search');
        File::makeDirectory($this->dir, 0755, true, true);

        file_put_contents($this->dir . '/a.blade.php', "first line\nneedle here\nlast line\n");
        file_put_contents($this->dir . '/b.txt', "needle in txt\n");
        file_put_contents($this->dir . '/big.blade.php', str_repeat('x', 1048577));
        file_put_contents($this->dir . '/bin.blade.php', "needle\0binary");
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);

        parent::tearDown();
    }

    public function testFindsMatchWithLineNumber(): void
    {
        $found = CodeSearcher::search('views', 'needle', '*.blade.php');

        $files = array_column($found['results'], 'file');

        $this->assertContains('page_editor_search/a.blade.php', $files);
        $this->assertSame(2, $found['results'][array_search('page_editor_search/a.blade.php', $files, true)]['line']);
    }

    public function testMaskFiltersFiles(): void
    {
        $found = CodeSearcher::search('views', 'needle', '*.blade.php');

        $this->assertNotContains('page_editor_search/b.txt', array_column($found['results'], 'file'));
    }

    public function testSkipsBinaryAndOversizedFiles(): void
    {
        $found = CodeSearcher::search('views', 'needle', '*.blade.php');
        $files = array_column($found['results'], 'file');

        $this->assertNotContains('page_editor_search/bin.blade.php', $files);
        $this->assertNotContains('page_editor_search/big.blade.php', $files);
    }

    public function testCaseSensitivity(): void
    {
        $this->assertNotEmpty(CodeSearcher::search('views', 'NEEDLE', '*.blade.php')['results']);
        $this->assertEmpty(CodeSearcher::search('views', 'NEEDLE', '*.blade.php', true)['results']);
    }

    public function testRegexMode(): void
    {
        $found = CodeSearcher::search('views', 'n[e]+dle', '*.blade.php', false, true);

        $this->assertNotEmpty($found['results']);
    }

    public function testTruncatesWhenResultsExceedLimit(): void
    {
        config()->set('page_editor.max_search_results', 2);

        file_put_contents($this->dir . '/many.blade.php', str_repeat("needle\n", 5));

        $found = CodeSearcher::search('views', 'needle', '*.blade.php');

        $this->assertCount(2, $found['results']);
        $this->assertTrue($found['truncated']);
    }

    public function testInvalidRegexDoesNotCrashAndReportsInvalidPattern(): void
    {
        $found = CodeSearcher::search('views', '[', '*.blade.php', false, true);

        $this->assertSame([], $found['results']);
        $this->assertFalse($found['truncated']);
        $this->assertTrue($found['invalid']);
    }

    public function testSearchPageRendersResults(): void
    {
        $boss = User::factory()->boss()->create(['login' => 'boss_search']);

        $response = $this->actingAs($boss)->get(route('admin.files.search', [
            'root'  => 'views',
            'query' => 'needle',
            'mask'  => '*.blade.php',
        ]));

        $response->assertOk();
        $response->assertSee('page_editor_search/a.blade.php');
    }

    public function testSearchPageShowsInvalidRegexMessage(): void
    {
        $boss = User::factory()->boss()->create(['login' => 'boss_search_invalid']);

        $response = $this->actingAs($boss)->get(route('admin.files.search', [
            'root'  => 'views',
            'query' => '[',
            'mask'  => '*.blade.php',
            'regex' => 1,
        ]));

        $response->assertOk();
        $response->assertSee(__('page_editor::files.regex_invalid'));
        $response->assertDontSee(__('page_editor::files.search_empty'));
    }

    public function testHighlightWrapsMatchAndEscapesRest(): void
    {
        $html = CodeSearcher::highlight('<b>site_rules</b>', 'site_rules');

        $this->assertSame('&lt;b&gt;<mark>site_rules</mark>&lt;/b&gt;', $html);
    }

    public function testHighlightEscapesMatchItself(): void
    {
        $html = CodeSearcher::highlight('a <script> b', '<script>');

        $this->assertSame('a <mark>&lt;script&gt;</mark> b', $html);
    }

    public function testHighlightIgnoresCaseByDefault(): void
    {
        $this->assertSame('<mark>Site</mark>', CodeSearcher::highlight('Site', 'site'));
        $this->assertSame('Site', CodeSearcher::highlight('Site', 'site', true));
    }

    public function testHighlightSurvivesEmptyRegexMatch(): void
    {
        $this->assertSame('abc', CodeSearcher::highlight('abc', 'x*', false, true));
    }

    public function testSearchPageHighlightsMatch(): void
    {
        $boss = User::factory()->boss()->create(['login' => 'boss_search_mark']);

        $response = $this->actingAs($boss)->get(route('admin.files.search', [
            'root'  => 'views',
            'query' => 'needle',
            'mask'  => '*.blade.php',
        ]));

        $response->assertOk();
        $response->assertSee('<mark>needle</mark>', false);
    }
}
