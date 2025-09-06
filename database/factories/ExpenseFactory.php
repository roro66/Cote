<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Expense;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'account_id' => null,
            'description' => $this->faker->sentence(),
            'status' => 'draft',
            'submitted_by' => null,
            'expense_date' => $this->faker->dateTimeBetween('-1 years', 'now'),
        ];
    }
}
