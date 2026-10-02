<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class PublicPropertySearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tags' => ['sometimes', 'array', 'max:20'],
            'tags.*' => ['string', 'max:100'],
            'beds' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'baths' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'min_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'featured' => ['sometimes', 'boolean'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
