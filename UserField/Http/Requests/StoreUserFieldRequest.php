<?php

namespace Modules\UserField\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\UserField\Models\UserField;

class StoreUserFieldRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type'     => 'required|in:' . implode(',', UserField::TYPES),
            'name'     => 'required|max:50',
            'min'      => 'required',
            'max'      => 'required',
            'required' => 'boolean',
        ];
    }
}
