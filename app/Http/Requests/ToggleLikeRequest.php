<?php

namespace App\Http\Requests;

use App\Models\Like;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ToggleLikeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Like::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The toggle takes no input — the post comes from the route and the
     * user from the authenticated token.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
