<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numero_client' => 'CLI-' . $this->faker->unique()->regexify('[A-Z0-9]{8}'),
            'nom' => $this->faker->lastName(),
            'prenom' => $this->faker->firstName(),
            'email' => $this->faker->unique()->safeEmail(),
            'telephone' => $this->faker->phoneNumber(),
            'date_naissance' => $this->faker->dateTimeBetween('-70 years', '-18 years'),
            'adresse' => $this->faker->streetAddress(),
            'ville' => $this->faker->city(),
            'code_postal' => $this->faker->postcode(),
            'pays' => $this->faker->country(),
            'statut' => $this->faker->randomElement(['actif', 'inactif', 'suspendu']),
        ];
    }

    public function actif()
    {
        return $this->state(function (array $attributes) {
            return [
                'statut' => 'actif',
            ];
        });
    }

    public function francais()
    {
        return $this->state(function (array $attributes) {
            return [
                'pays' => 'France',
                'ville' => $this->faker->city(),
                'code_postal' => $this->faker->regexify('[0-9]{5}'),
            ];
        });
    }
}
