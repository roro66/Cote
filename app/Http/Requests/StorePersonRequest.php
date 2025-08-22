<?php

namespace App\Http\Requests;

use App\Rules\ValidChileanRut;
use Illuminate\Foundation\Http\FormRequest;

class StorePersonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->is_enabled;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $personId = $this->route('person') ? $this->route('person')->id : null;

        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'rut' => [
                'required',
                'string',
                'unique:people,rut' . ($personId ? ',' . $personId : ''),
                new ValidChileanRut()
            ],
            'email' => 'required|email|unique:people,email' . ($personId ? ',' . $personId : ''),
            'phone' => 'nullable|string|max:20',
            'role_type' => 'required|in:team_leader,team_member,supervisor,admin',
            'is_enabled' => 'boolean',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'first_name' => 'nombre',
            'last_name' => 'apellido',
            'rut' => 'RUT',
            'email' => 'correo electrónico',
            'phone' => 'teléfono',
            'role_type' => 'tipo de rol',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'El nombre es obligatorio.',
            'last_name.required' => 'El apellido es obligatorio.',
            'rut.required' => 'El RUT es obligatorio.',
            'rut.unique' => 'Este RUT ya está registrado.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Debe ingresar un correo electrónico válido.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'role_type.required' => 'Debe seleccionar un tipo de rol.',
            'role_type.in' => 'El tipo de rol seleccionado no es válido.',
        ];
    }
}
