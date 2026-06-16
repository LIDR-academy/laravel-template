<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Ownership (403) is enforced in the controller via PostPolicy, after the
        // 404 check, to preserve the legacy "not found beats forbidden" ordering.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'body' => 'sometimes|required|string',
            'published' => 'sometimes|boolean',
            'tags' => 'sometimes|array',
            'tags.*' => 'integer|exists:tags,id',
            'categories' => 'sometimes|array',
            'categories.*' => 'integer|exists:categories,id',
        ];
    }
}
