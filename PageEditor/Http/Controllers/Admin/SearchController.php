<?php

declare(strict_types=1);

namespace Modules\PageEditor\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\PageEditor\Support\CodeSearcher;
use Modules\PageEditor\Support\PathResolver;

class SearchController extends Controller
{
    /**
     * Поиск по коду
     */
    public function index(Request $request): View
    {
        $roots = config('page_editor.search_roots', []);
        $root = (string) $request->input('root', $roots[0] ?? 'views');

        if (! in_array($root, $roots, true)) {
            abort(404);
        }

        $query = (string) $request->input('query', '');
        $mask = (string) $request->input('mask', '*');
        $caseSensitive = $request->boolean('case');
        $regex = $request->boolean('regex');

        $found = CodeSearcher::search($root, $query, $mask, $caseSensitive, $regex);

        return view('page_editor::admin/files/search', [
            'roots'     => $roots,
            'readOnly'  => PathResolver::isReadOnly($root),
            'root'      => $root,
            'query'     => $query,
            'mask'      => $mask,
            'case'      => $caseSensitive,
            'regex'     => $regex,
            'results'   => $found['results'],
            'truncated' => $found['truncated'],
            'invalid'   => $found['invalid'],
        ]);
    }
}
