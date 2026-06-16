<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreLikeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Any authenticated user may like; route auth:sanctum gates this.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The toggle takes no body — the post is identified by the route. There is
     * nothing to validate, but the FormRequest still owns authorization and
     * keeps the controller thin, matching the established layer pattern.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
