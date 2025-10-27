<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CompteBancaire>
 */
class CompteBancaireFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numero_compte' => 'CB-' . $this->faker->unique()->regexify('[A-Z0-9]{10}'),
            'client_id' => \App\Models\Client::factory(),
            'type_compte' => $this->faker->randomElement(['courant', 'epargne', 'joint']),
            'devise' => $this->faker->randomElement(['EUR', 'USD', 'GBP']),
            'decouvert_autorise' => $this->faker->randomFloat(2, 0, 2000),
            'date_ouverture' => $this->faker->dateTimeBetween('-10 years', 'now'),
            'statut' => $this->faker->randomElement(['actif', 'bloque', 'ferme']),
            'commentaires' => $this->faker->optional(0.3)->sentence(),
            'est_bloque' => false,
            'est_archive' => false,
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

    public function courant()
    {
        return $this->state(function (array $attributes) {
            return [
                'type_compte' => 'courant',
            ];
        });
    }

    public function epargne()
    {
        return $this->state(function (array $attributes) {
            return [
                'type_compte' => 'epargne',
            ];
        });
    }

    public function positif()
    {
        return $this->state(function (array $attributes) {
            return [
                'solde' => $this->faker->randomFloat(2, 100, 50000),
            ];
        });
    }
}
