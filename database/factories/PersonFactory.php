<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Person>
 */
class PersonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generar RUT chileno válido
        $number = $this->faker->numberBetween(1000000, 25000000);
        $dv = $this->calculateDV($number);
        $rut = number_format($number, 0, '', '.') . '-' . $dv;
        
        $banks = ['Banco de Chile', 'Banco Santander', 'BCI', 'Banco Estado', 'Itaú', 'Scotiabank', 'Banco Falabella'];
        $accountTypes = ['corriente', 'vista', 'ahorro'];
        
        return [
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName() . ' ' . $this->faker->lastName(),
            'rut' => $rut,
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->optional(0.8)->phoneNumber(),
            'bank_name' => $this->faker->optional(0.7)->randomElement($banks),
            'account_type' => $this->faker->optional(0.7)->randomElement($accountTypes),
            'account_number' => $this->faker->optional(0.7)->numerify('##########'),
            'address' => $this->faker->optional(0.6)->address(),
            'role_type' => $this->faker->randomElement(['tesorero', 'trabajador']),
            'is_enabled' => $this->faker->boolean(85), // 85% activos
            'created_at' => $this->faker->dateTimeBetween('-2 years', 'now'),
            'updated_at' => now(),
        ];
    }
    
    /**
     * Calcular dígito verificador de RUT chileno
     */
    private function calculateDV(int $number): string
    {
        $sum = 0;
        $multiplier = 2;
        
        while ($number > 0) {
            $sum += ($number % 10) * $multiplier;
            $number = intval($number / 10);
            $multiplier++;
            if ($multiplier > 7) {
                $multiplier = 2;
            }
        }
        
        $remainder = $sum % 11;
        $dv = 11 - $remainder;
        
        if ($dv == 11) {
            return '0';
        } elseif ($dv == 10) {
            return 'K';
        } else {
            return (string) $dv;
        }
    }
}
