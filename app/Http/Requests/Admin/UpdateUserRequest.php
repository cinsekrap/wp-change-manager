<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $this->route('user')->id,
            'password' => ['nullable', 'confirmed', Password::min(10)->mixedCase()->numbers()],
            'is_active' => 'boolean',
            'role' => 'nullable|in:editor,super_admin',
        ];
    }

    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated($key, $default);

        if ($key !== null) {
            return $data;
        }

        $data['is_active'] = $this->boolean('is_active');
        $data['role'] = $this->input('role') ?: null;

        if (empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }
}
