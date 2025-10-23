<?php

namespace App\Console\Commands;

use App\Jobs\ArchiverTransactionsJournalieres;
use Illuminate\Console\Command;

class ArchiverTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:archiver {--date= : Date spécifique (YYYY-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Archiver les transactions journalières vers la base Neon';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->option('date') ? \Carbon\Carbon::parse($this->option('date')) : today();

        $this->info("Archivage des transactions du {$date->format('Y-m-d')}");

        // Dispatcher le job
        ArchiverTransactionsJournalieres::dispatch();

        $this->info('Job d\'archivage lancé avec succès');
    }
}