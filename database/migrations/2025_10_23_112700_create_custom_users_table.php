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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero_user')->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['admin', 'manager', 'user'])->default('user');
            $table->enum('statut', ['actif', 'inactif', 'suspendu'])->default('actif');
            $table->rememberToken();
            $table->timestamps();

            // Polymorphisme pour Client/Admin
            $table->nullableMorphs('authenticatable');

            // Index
            $table->index('role');
            $table->index('statut');
            $table->index('numero_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
