<?php

namespace Database\Seeders;

use App\Helpers\RutHelper;
use App\Models\Account;
use App\Models\Person;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class AccountSeeder extends Seeder
{
	/**
	 * Crear personas y sus cuentas.
	 */
	public function run(): void
	{
		$faker = Faker::create('es_CL');

		// Asegurar al menos 30 personas
		$target = 30;
		$current = Person::count();
		$toCreate = max(0, $target - $current);

	$roles = ['tesorero', 'trabajador'];

		for ($i = 0; $i < $toCreate; $i++) {
			// Generar RUT único válido
			do {
				$number = $faker->numberBetween(1_000_000, 25_000_000);
				$rut = RutHelper::format(RutHelper::generate($number));
			} while (Person::where('rut', RutHelper::clean($rut))->exists());

			$person = new Person();
			$person->first_name = $faker->firstName();
			$person->last_name = $faker->lastName() . ' ' . $faker->lastName();
			$person->rut = $rut; // setter lo limpiará
			$person->email = $faker->unique()->safeEmail();
			$person->phone = $faker->optional(0.8)->phoneNumber();
			$person->account_number = $faker->optional(0.7)->numerify('##########');
			$person->address = $faker->optional(0.6)->address();
			$person->role_type = $faker->randomElement($roles);
			$person->is_enabled = $faker->boolean(90);
			$person->save();
		}

		// Crear una cuenta por persona si no tiene
		Person::query()->chunkById(200, function ($people) use ($faker) {
			foreach ($people as $p) {
				if (!$p->accounts()->exists()) {
					Account::firstOrCreate(
						[
							'person_id' => $p->id,
							'type' => 'person',
						],
						[
							'name' => ($p->name ?? trim($p->first_name . ' ' . $p->last_name)),
							'balance' => 0,
							'notes' => null,
							'is_enabled' => true,
						]
					);
				}
			}
		});
	}
}

