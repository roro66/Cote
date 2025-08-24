<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'rut' => $this->rut,
            'rut_formatted' => $this->rut_formatted,
            'email' => $this->email,
            'phone' => $this->phone,
            'role_type' => $this->role_type,
            'role_type_spanish' => $this->role_type_spanish,
            'bank_id' => $this->bank_id,
            'bank_name' => $this->bank?->name,
            'bank_type' => $this->bank?->type,
            'account_type_id' => $this->account_type_id,
            'account_type_name' => $this->accountType?->name,
            'account_number' => $this->account_number,
            'address' => $this->address,
            'is_enabled' => $this->is_enabled,
            'status' => $this->is_enabled ? 'Activo' : 'Inactivo',
            'created_at' => $this->created_at?->format('d/m/Y H:i'),
            'updated_at' => $this->updated_at?->format('d/m/Y H:i'),
        ];
    }
}
