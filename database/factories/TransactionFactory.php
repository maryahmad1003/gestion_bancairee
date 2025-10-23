<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['debit', 'credit', 'virement_emis', 'virement_recus']);
        $montant = $this->faker->randomFloat(2, 10, 5000);

        return [
            'numero_transaction' => 'TXN-' . $this->faker->unique()->regexify('[A-Z0-9]{12}'),
            'compte_bancaire_id' => \App\Models\CompteBancaire::factory(),
            'compte_bancaire_destinataire_id' => in_array($type, ['virement_emis', 'virement_recus'])
                ? \App\Models\CompteBancaire::factory()
                : null,
            'type_transaction' => $type,
            'montant' => $montant,
            'devise' => $this->faker->randomElement(['EUR', 'USD', 'GBP']),
            'libelle' => $this->faker->sentence(3),
            'description' => $this->faker->optional(0.7)->paragraph(),
            'date_transaction' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'statut' => $this->faker->randomElement(['en_attente', 'validee', 'rejete', 'annule']),
            'reference_externe' => $this->faker->optional(0.5)->uuid(),
            'metadata' => $this->faker->optional(0.3)->passthrough([
                'canal' => $this->faker->randomElement(['web', 'mobile', 'agence', 'api']),
                'ip_source' => $this->faker->ipv4(),
                'user_agent' => $this->faker->userAgent(),
            ]),
        ];
    }

    public function validee()
    {
        return $this->state(function (array $attributes) {
            return [
                'statut' => 'validee',
            ];
        });
    }

    public function debit()
    {
        return $this->state(function (array $attributes) {
            return [
                'type_transaction' => 'debit',
            ];
        });
    }

    public function credit()
    {
        return $this->state(function (array $attributes) {
            return [
                'type_transaction' => 'credit',
            ];
        });
    }

    public function recente()
    {
        return $this->state(function (array $attributes) {
            return [
                'date_transaction' => $this->faker->dateTimeBetween('-1 month', 'now'),
            ];
        });
    }
}
