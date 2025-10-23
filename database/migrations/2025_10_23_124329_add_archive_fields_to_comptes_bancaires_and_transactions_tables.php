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
           $table->boolean('est_archive')->default(false)->after('motif_blocage');
           $table->dateTime('date_archivage')->nullable()->after('est_archive');
       });

       Schema::table('transactions', function (Blueprint $table) {
           $table->boolean('est_archive')->default(false)->after('metadata');
           $table->dateTime('date_archivage')->nullable()->after('est_archive');
       });
   }

    /**
     * Reverse the migrations.
     */
    public function down(): void
   {
       Schema::table('comptes_bancaires', function (Blueprint $table) {
           $table->dropColumn(['est_archive', 'date_archivage']);
       });

       Schema::table('transactions', function (Blueprint $table) {
           $table->dropColumn(['est_archive', 'date_archivage']);
       });
   }
};
