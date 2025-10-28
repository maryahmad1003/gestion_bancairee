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
            $table->string('motif_archivage')->nullable()->after('date_archivage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comptes_bancaires', function (Blueprint $table) {
            $table->dropColumn('motif_archivage');
        });
    }
};
