<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MediaUploadRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('manage-content') === true; }
    public function rules(): array { return ['file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'], 'alt' => ['nullable', 'string', 'max:255']]; }
}