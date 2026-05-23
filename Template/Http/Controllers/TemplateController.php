<?php

declare(strict_types=1);

namespace Modules\Template\Http\Controllers;

use App\Http\Controllers\Controller;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\Template\Models\Template;

class TemplateController extends Controller
{
    public function index(): View
    {
        $templates = Template::query()
            ->orderByDesc('created_at')
            ->with('user')
            ->paginate(20);

        return view('template::index', compact('templates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:100'],
            'text'  => [
                'required',
                'string',
                function (string $attr, mixed $value, Closure $fail) {
                    $length = mb_strlen(trim(strip_tags((string) $value)));
                    if ($length < 5 || $length > 1000) {
                        $fail(__('validator.text'));
                    }
                },
            ],
        ]);

        Template::query()->create([
            'user_id'    => $request->user()->id,
            'title'      => $data['title'],
            'text'       => $data['text'],
            'created_at' => SITETIME,
        ]);

        Cache::forget('statTemplate');

        setFlash('success', __('template::template.record_added'));

        return redirect()->route('template.index');
    }
}
