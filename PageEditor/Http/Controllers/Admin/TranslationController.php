<?php

declare(strict_types=1);

namespace Modules\PageEditor\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\PageEditor\Support\PathResolver;
use Modules\PageEditor\Support\TranslationRepository;

class TranslationController extends Controller
{
    /**
     * Редактор переводов
     */
    public function index(Request $request): View
    {
        $groups = TranslationRepository::groups();
        $locales = TranslationRepository::locales();
        $query = (string) $request->input('query', '');

        $label = (string) $request->input('group', $groups[0]['label'] ?? '');
        $current = collect($groups)->firstWhere('label', $label) ?: ($groups[0] ?? null);

        if ($query !== '') {
            return view('page_editor::admin/files/translations', [
                'roots'     => array_keys(PathResolver::roots()),
                'groups'    => $groups,
                'locales'   => $locales,
                'current'   => $current,
                'query'     => $query,
                'lines'     => [],
                'overrides' => [],
                'found'     => TranslationRepository::search($query),
            ]);
        }

        $lines = $current
            ? TranslationRepository::lines($current['namespace'], $current['group'])
            : [];

        // Ключи, лежащие в overlay-файлах группы: вьюха помечает их и рисует сброс
        $overrides = $current
            ? TranslationRepository::overrides($current['namespace'], $current['group'])
            : [];

        return view('page_editor::admin/files/translations', [
            'roots'     => array_keys(PathResolver::roots()),
            'groups'    => $groups,
            'locales'   => $locales,
            'current'   => $current,
            'query'     => '',
            'lines'     => $lines,
            'overrides' => $overrides,
            'found'     => [],
        ]);
    }

    /**
     * Сохранение переводов
     *
     * namespace и group приходят из запроса и участвуют в построении пути к overlay-файлу
     * (TranslationRepository::overlayPath()), поэтому оба прогоняются через
     * PathResolver::normalize() и сверяются с регуляркой |^[a-z0-9_]+$| — подделанное значение
     * вида "../../" не должно увести запись файла за пределы resources/custom/lang.
     *
     * locale приходит ключом массива из формы (lines[ключ][локаль]) и так же участвует
     * в построении overlay-пути, поэтому сверяется белым списком TranslationRepository::locales()
     * — реальных подкаталогов resources/lang. Любое значение вне этого списка (например
     * "../../../../../../public/shell") получает 404 ещё до вызова TranslationRepository::save()
     */
    public function save(Request $request): RedirectResponse
    {
        $pattern = '|^[a-z0-9_]+$|';

        $namespace = PathResolver::normalize((string) $request->input('namespace', ''));
        $group = PathResolver::normalize((string) $request->input('group', ''));

        if ($namespace !== '' && ! preg_match($pattern, $namespace)) {
            abort(404);
        }

        if (! preg_match($pattern, $group)) {
            abort(404);
        }

        $namespace = $namespace === '' ? null : $namespace;
        $lines = (array) $request->input('lines', []);

        $byLocale = [];

        foreach ($lines as $key => $values) {
            foreach ((array) $values as $locale => $value) {
                $byLocale[$locale][$key] = (string) $value;
            }
        }

        // Сброс приходит чекбоксами reset[ключ][локаль] — пара «ключ + локаль»,
        // которую надо убрать из overlay, вернув исходное значение
        $resetByLocale = [];

        foreach ((array) $request->input('reset', []) as $key => $values) {
            foreach ((array) $values as $locale => $flag) {
                $resetByLocale[$locale][] = (string) $key;
            }
        }

        $allowedLocales = TranslationRepository::locales();

        foreach (array_keys($byLocale + $resetByLocale) as $locale) {
            if (! in_array((string) $locale, $allowedLocales, true)) {
                abort(404);
            }
        }

        foreach (array_keys($byLocale + $resetByLocale) as $locale) {
            TranslationRepository::save(
                $namespace,
                $group,
                (string) $locale,
                $byLocale[$locale] ?? [],
                $resetByLocale[$locale] ?? [],
            );
        }

        $label = $namespace === null ? $group : $namespace . '::' . $group;

        return redirect()->route('admin.files.translations', ['group' => $label])
            ->with('flash.success', __('page_editor::files.translations_saved'));
    }
}
