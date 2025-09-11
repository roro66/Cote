<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;

class UpdateUserRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && auth()->user()->hasRole('boss');
    }

    public function rules()
    {
        $userId = $this->route('user')->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_enabled' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $roles = $this->input('roles', []);
            $userId = $this->route('user')->id;
            if (in_array('treasurer', $roles)) {
                $other = User::role('treasurer')->where('id', '<>', $userId)->first();
                if ($other) {
                    $validator->errors()->add('roles', 'Ya existe un tesorero asignado. Quita ese rol antes de asignarlo a otro usuario.');
                }
            }
        });
    }
}
