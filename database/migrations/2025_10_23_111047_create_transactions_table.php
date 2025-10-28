<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero_transaction')->unique();
            $table->uuid('compte_bancaire_id');
            $table->uuid('compte_bancaire_destinataire_id')->nullable(); // Pour les virements
            $table->enum('type_transaction', ['debit', 'credit', 'virement_emis', 'virement_recus']);
            $table->decimal('montant', 15, 2);
            $table->string('devise', 3)->default('XOF');
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->dateTime('date_transaction');
            $table->enum('statut', ['en_attente', 'validee', 'rejete', 'annule'])->default('validee');
            $table->string('reference_externe')->nullable();
            $table->json('metadata')->nullable(); // Pour stocker des données supplémentaires
            $table->timestamps();

            // Clés étrangères et index
            $table->foreign('compte_bancaire_id')->references('id')->on('comptes_bancaires')->onDelete('cascade');
            $table->foreign('compte_bancaire_destinataire_id')->references('id')->on('comptes_bancaires')->onDelete('set null');
            $table->index(['compte_bancaire_id', 'date_transaction']);
            $table->index('type_transaction');
            $table->index('statut');
            $table->index('numero_transaction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
