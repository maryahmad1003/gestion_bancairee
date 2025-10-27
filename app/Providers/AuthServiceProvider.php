<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Définir les scopes Passport pour les permissions
        \Laravel\Passport\Passport::tokensCan([
            'view_all_clients' => 'Voir tous les clients',
            'manage_clients' => 'Gérer les clients',
            'view_all_accounts' => 'Voir tous les comptes',
            'manage_accounts' => 'Gérer les comptes',
            'view_all_transactions' => 'Voir toutes les transactions',
            'manage_transactions' => 'Gérer les transactions',
            'archive_accounts' => 'Archiver les comptes',
            'view_logs' => 'Voir les logs',
            'manage_users' => 'Gérer les utilisateurs',
            'view_own_accounts' => 'Voir ses propres comptes',
            'manage_own_accounts' => 'Gérer ses propres comptes',
            'view_own_transactions' => 'Voir ses propres transactions',
            'create_transactions' => 'Créer des transactions',
        ]);

        // Définir les claims personnalisés pour le rôle
        \Laravel\Passport\Passport::useTokenModel(\Laravel\Passport\Token::class);
    }
}
