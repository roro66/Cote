<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersonBankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'person_id',
        'bank_id',
        'account_type_id',
        'account_number',
        'alias',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function accountType()
    {
        return $this->belongsTo(AccountType::class);
    }
}
