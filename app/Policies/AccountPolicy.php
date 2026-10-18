<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    /** También decide en qué cuentas puede rendir gastos el usuario. */
    public function view(User $user, Account $account): bool
    {
        return $user->hasAnyRole(['boss', 'treasurer'])
            || ($user->person_id !== null && (int) $account->person_id === (int) $user->person_id);
    }
}
