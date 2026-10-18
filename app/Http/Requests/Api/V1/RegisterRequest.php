<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            'role' => ['prohibited'],
            'role_id' => ['prohibited'],
            'role_slug' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role.prohibited' => 'A role cannot be chosen during registration.',
            'role_id.prohibited' => 'A role cannot be chosen during registration.',
            'role_slug.prohibited' => 'A role cannot be chosen during registration.',
        ];
    }
}
