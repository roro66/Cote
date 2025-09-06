<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Transaction;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'from_account_id' => null,
            'to_account_id' => null,
            'amount' => $this->faker->numberBetween(1000, 500000),
            'status' => 'pending',
            'type' => 'transfer',
            'submitted_by' => null,
        ];
    }
}
