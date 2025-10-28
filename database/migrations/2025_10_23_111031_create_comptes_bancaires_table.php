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
        Schema::create('comptes_bancaires', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero_compte')->unique();
            $table->uuid('client_id');
            $table->string('type_compte')->default('courant'); // courant, epargne, joint
            $table->string('devise', 3)->default('XOF');
            // $table->decimal('solde', 15, 2)->default(0); // Supprimé car calculé dynamiquement
            $table->decimal('decouvert_autorise', 10, 2)->default(0);
            $table->date('date_ouverture');
            $table->enum('statut', ['actif', 'bloque', 'ferme'])->default('actif');
            $table->text('commentaires')->nullable();
            $table->timestamps();

            // Clés étrangères et index
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->index(['client_id', 'statut']);
            $table->index('numero_compte');
            $table->index('type_compte');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comptes_bancaires');
    }
};
