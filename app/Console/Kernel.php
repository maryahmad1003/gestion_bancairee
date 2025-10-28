<?php

namespace App\Console;

use App\Jobs\ArchiverComptesBloquesExpires;
use App\Jobs\DebloquerComptesEpargne;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Archiver les transactions tous les jours à minuit
        $schedule->command('transactions:archiver')
            ->dailyAt('00:00')
            ->withoutOverlapping()
            ->runInBackground();

        // Synchroniser les comptes quotidiens vers le cloud à minuit (heure du Sénégal)
        // UTC+0 = minuit UTC, ce qui correspond à minuit au Sénégal (UTC+0)
        $schedule->command('sync:daily-accounts')
            ->dailyAt('00:00')
            ->timezone('Africa/Dakar')
            ->withoutOverlapping()
            ->runInBackground()
            ->name('sync-daily-accounts-senegal')
            ->description('Synchronisation quotidienne des comptes vers le cloud');

        // Débloquer automatiquement les comptes épargne dont la date de fin de blocage est échue
        $schedule->job(DebloquerComptesEpargne::class)
            ->dailyAt('01:00')
            ->timezone('Africa/Dakar')
            ->withoutOverlapping()
            ->runInBackground()
            ->name('debloquer-comptes-epargne')
            ->description('Déblocage automatique des comptes épargne expirés');

        // Archiver automatiquement les comptes épargne bloqués depuis plus de 30 jours
        $schedule->job(ArchiverComptesBloquesExpires::class)
            ->dailyAt('02:00')
            ->timezone('Africa/Dakar')
            ->withoutOverlapping()
            ->runInBackground()
            ->name('archiver-comptes-bloques-expires')
            ->description('Archivage automatique des comptes épargne bloqués expirés');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
