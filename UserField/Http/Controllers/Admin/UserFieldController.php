<?php

declare(strict_types=1);

namespace Modules\UserField\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\UserField\Http\Requests\StoreUserFieldRequest;
use Modules\UserField\Models\UserField;

class UserFieldController extends AdminController
{
    /**
     * List user fields
     */
    public function index(): View
    {
        $fields = UserField::query()
            ->orderBy('sort')
            ->get();

        return view('user_field::admin/index', compact('fields'));
    }

    /**
     * Create field
     */
    public function create(): View
    {
        $types = UserField::TYPES;
        $field = new UserField();

        return view('user_field::admin/create', compact('field', 'types'));
    }

    public function store(StoreUserFieldRequest $request): RedirectResponse
    {
        UserField::query()->create($request->all());

        return redirect('admin/user-fields')->with('success', __('main.record_added_success'));
    }

    /**
     * Change field
     */
    public function edit(int $id): View
    {
        $types = UserField::TYPES;

        $field = UserField::query()->find($id);

        if (! $field) {
            abort(404, __('user_field::user_fields.not_found'));
        }

        return view('user_field::admin/edit', compact('field', 'types'));
    }

    public function update(int $id, StoreUserFieldRequest $request): RedirectResponse
    {
        $field = UserField::query()->find($id);

        if (! $field) {
            abort(404, __('user_field::user_fields.not_found'));
        }

        $field->update($request->all());

        return redirect('admin/user-fields')->with('success', __('main.record_saved_success'));
    }

    /**
     * Delete field
     */
    public function destroy(int $id): JsonResponse
    {
        $field = UserField::query()->find($id);

        if (! $field) {
            return response()->json([
                'success' => false,
                'message' => __('user_field::user_fields.not_found'),
            ]);
        }

        $field->data()->delete();
        $field->delete();

        return response()->json([
            'success' => true,
            'message' => __('main.record_deleted_success'),
        ]);
    }
}
