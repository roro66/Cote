<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Account;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'type' => $this->faker->randomElement(['person','treasury']),
            'person_id' => null,
            'balance' => $this->faker->numberBetween(0, 500000),
            'notes' => $this->faker->optional()->sentence(),
            'is_enabled' => true,
        ];
    }
}
