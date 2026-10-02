<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-content') === true;
    }

    public function rules(): array
    {
        $menuId = $this->route('id');
        $creating = $this->isMethod('post');

        return [
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:255'],
            'location' => [$creating ? 'required' : 'sometimes', 'string', 'max:100', Rule::unique('menus', 'location')->ignore($menuId)],
            'order' => ['sometimes', 'integer', 'min:0'],
            'published' => ['sometimes', 'boolean'],
            'items' => ['sometimes', 'array'],
            'items.*.id' => ['sometimes', 'integer', 'distinct'],
            'items.*.client_key' => ['sometimes', 'string', 'max:100'],
            'items.*.parent_id' => ['nullable', 'integer'],
            'items.*.parent_client_key' => ['nullable', 'string', 'max:100'],
            'items.*.label' => ['required_with:items', 'string', 'max:255'],
            'items.*.url' => ['required_with:items', 'string', 'max:2048'],
            'items.*.order' => ['sometimes', 'integer', 'min:0'],
            'items.*.published' => ['sometimes', 'boolean'],
        ];
    }
}