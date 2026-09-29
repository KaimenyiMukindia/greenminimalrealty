<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Actions\Auth\AuditAuthEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class LoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $this->merge(['email' => mb_strtolower(trim($this->email))]);
        }
    }

    public function rules(): array
    {
        return ['email' => ['required', 'email:rfc', 'max:255'], 'password' => ['required', 'string']];
    }

    protected function failedValidation(Validator $validator): void
    {
        $email = $this->input('email');
        app(AuditAuthEvent::class)->log($this, 'auth.login_failed', null, [
            'email' => is_string($email) ? mb_substr(trim($email), 0, 255) : null,
            'reason' => 'invalid_request',
        ]);

        parent::failedValidation($validator);
    }
}