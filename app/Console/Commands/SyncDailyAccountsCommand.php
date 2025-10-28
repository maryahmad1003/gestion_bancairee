<?php

namespace App\Console\Commands;

use App\Jobs\SyncDailyAccountsToCloud;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SyncDailyAccountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:daily-accounts {--date= : Date spécifique pour la synchronisation (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchroniser les comptes créés dans la journée vers la base de données cloud (heure du Sénégal)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Démarrage de la synchronisation des comptes quotidiens vers le cloud...');

        // Vérifier si une date spécifique est fournie
        $specificDate = $this->option('date');

        if ($specificDate) {
            $this->info("📅 Synchronisation pour la date spécifique : {$specificDate}");
        } else {
            // Utiliser la date d'aujourd'hui en heure du Sénégal
            $senegalTimezone = 'Africa/Dakar';
            $today = Carbon::now($senegalTimezone)->format('Y-m-d');
            $this->info("📅 Synchronisation pour aujourd'hui (heure du Sénégal) : {$today}");
        }

        try {
            // Dispatcher le job
            SyncDailyAccountsToCloud::dispatch();

            $this->info('✅ Job de synchronisation dispatché avec succès !');
            $this->info('📊 Le job sera traité en arrière-plan par la queue.');

            // Informations supplémentaires
            $this->line('');
            $this->comment('💡 Pour vérifier le statut du job :');
            $this->comment('   php artisan queue:work --once');
            $this->comment('   php artisan queue:status');

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors du dispatch du job : ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
