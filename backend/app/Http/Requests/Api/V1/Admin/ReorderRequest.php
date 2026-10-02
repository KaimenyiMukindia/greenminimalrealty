<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReorderRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('manage-content') === true; }
    public function rules(): array { return ['ids' => ['required', 'array'], 'ids.*' => ['integer', 'distinct'], 'menu_id' => ['sometimes', 'integer', 'exists:menus,id']]; }
}