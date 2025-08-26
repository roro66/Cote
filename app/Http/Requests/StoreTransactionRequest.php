<?php

namespace App\Http\Requests;

use App\Services\TransactionService;
use App\Models\Account;
use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
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
        return [
            'type' => 'required|in:transfer',
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_id' => 'required|exists:accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'type' => 'tipo de transacción',
            'from_account_id' => 'cuenta origen',
            'to_account_id' => 'cuenta destino',
            'amount' => 'monto',
            'description' => 'descripción',
            'notes' => 'notas',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'El tipo de transacción es obligatorio.',
            'type.in' => 'El tipo de transacción debe ser transferencia.',
            'from_account_id.required' => 'Debe seleccionar una cuenta de origen.',
            'from_account_id.exists' => 'La cuenta de origen no existe.',
            'to_account_id.required' => 'Debe seleccionar una cuenta de destino.',
            'to_account_id.exists' => 'La cuenta de destino no existe.',
            'to_account_id.different' => 'La cuenta de destino debe ser diferente a la de origen.',
            'amount.required' => 'El monto es obligatorio.',
            'amount.numeric' => 'El monto debe ser un número.',
            'amount.min' => 'El monto debe ser mayor a 0.',
            'description.required' => 'La descripción es obligatoria.',
            'description.max' => 'La descripción no puede exceder 500 caracteres.',
            'notes.max' => 'Las notas no pueden exceder 1000 caracteres.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Verificar fondos suficientes para transferencias
            if ($this->type === 'transfer') {
                $transactionService = new TransactionService();
                if (!$transactionService->hasSufficientFunds($this->from_account_id, $this->amount)) {
                    $validator->errors()->add('amount', 'La cuenta origen no tiene fondos suficientes para esta transacción.');
                }

                // Validar que la transferencia sea únicamente Tesorería -> Persona o Persona -> Tesorería
                $from = Account::find($this->from_account_id);
                $to = Account::find($this->to_account_id);
                if ($from && $to) {
                    $isTreasuryToPerson = $from->type === 'treasury' && $to->type === 'person';
                    $isPersonToTreasury = $from->type === 'person' && $to->type === 'treasury';
                    if (!($isTreasuryToPerson || $isPersonToTreasury)) {
                        $validator->errors()->add('to_account_id', 'Solo se permiten transferencias entre Tesorería y cuentas personales (en ambos sentidos).');
                    }
                }
            }
        });
    }
}
