<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;

class StoreUserRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->hasRole('boss');
    }

    public function rules()
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_enabled' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $roles = $this->input('roles', []);
            if (in_array('treasurer', $roles)) {
                // if any user already has treasurer role, block
                if (User::role('treasurer')->exists()) {
                    $validator->errors()->add('roles', 'Ya existe un tesorero asignado. Quita ese rol antes de asignarlo a otro usuario.');
                }
            }
        });
    }
}
