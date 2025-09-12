<?php

use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Person;
use App\Models\Account;

if (!function_exists('create_role')) {
    function create_role(string $name)
    {
        return Role::firstOrCreate(['name' => $name]);
    }
}

if (!function_exists('create_treasurer_user')) {
    /**
     * Create a user and assign the 'treasurer' role.
     * Returns the created User instance.
     */
    function create_treasurer_user(array $attrs = []): User
    {
        create_role('treasurer');
        $user = User::factory()->create($attrs);
        $user->assignRole('treasurer');
        return $user;
    }
}

if (!function_exists('create_person_with_account')) {
    /**
     * Create a Person and an associated Account with given balances/attrs.
     * Returns array [Person, Account].
     */
    function create_person_with_account(array $personAttrs = [], array $accountAttrs = [])
    {
        $person = Person::factory()->create($personAttrs);

        $defaults = array_merge([
            'name' => 'Cuenta prueba',
            'type' => 'person',
            'person_id' => $person->id,
            'balance' => 100000,
            'is_enabled' => true,
        ], $accountAttrs);

        $account = Account::create($defaults);

        return [$person, $account];
    }
}
