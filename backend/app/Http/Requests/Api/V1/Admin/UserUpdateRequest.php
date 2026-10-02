<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::Admin;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'email' => ['sometimes', 'required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($this->route('id'))],
            'role' => ['sometimes', 'required', Rule::in(array_column(UserRole::cases(), 'value'))],
        ];
    }
}