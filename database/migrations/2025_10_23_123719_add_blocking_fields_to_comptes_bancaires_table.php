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
       Schema::table('comptes_bancaires', function (Blueprint $table) {
           $table->boolean('est_bloque')->default(false)->after('statut');
           $table->dateTime('date_debut_blocage')->nullable()->after('est_bloque');
           $table->integer('duree_blocage_jours')->nullable()->after('date_debut_blocage');
           $table->dateTime('date_fin_blocage')->nullable()->after('duree_blocage_jours');
           $table->text('motif_blocage')->nullable()->after('date_fin_blocage');
       });
   }

    /**
     * Reverse the migrations.
     */
    public function down(): void
   {
       Schema::table('comptes_bancaires', function (Blueprint $table) {
           $table->dropColumn(['est_bloque', 'date_debut_blocage', 'duree_blocage_jours', 'date_fin_blocage', 'motif_blocage']);
       });
   }
};
