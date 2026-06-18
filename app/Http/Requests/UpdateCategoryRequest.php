<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * The category is resolved via implicit route-model binding, so a missing
     * category short-circuits to 404 before this runs. Categories have no
     * owner, so authentication (auth:sanctum) is the only boundary — no Policy.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Partial update: `name` is optional but, when present, must still be
     * non-empty and unique — ignoring the category being updated.
     * `description` is optional free text.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($this->route('category')),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
