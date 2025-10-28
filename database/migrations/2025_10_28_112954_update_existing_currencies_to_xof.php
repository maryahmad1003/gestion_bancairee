<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Mettre à jour toutes les devises existantes vers XOF
        DB::table('comptes_bancaires')->update(['devise' => 'XOF']);
        DB::table('transactions')->update(['devise' => 'XOF']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comptes_bancaires', function (Blueprint $table) {
            //
        });
    }
};
