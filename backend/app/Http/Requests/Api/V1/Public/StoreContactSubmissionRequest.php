<?php

namespace App\Http\Requests\Api\V1\Public;

use Illuminate\Foundation\Http\FormRequest;

class StoreContactSubmissionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'interest' => ['nullable', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }
}