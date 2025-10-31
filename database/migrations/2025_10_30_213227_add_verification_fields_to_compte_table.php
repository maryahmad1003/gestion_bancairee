<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('compte', function (Blueprint $table) {
        $table->timestamp('email_verified_at')->nullable();
        $table->string('verification_code', 6)->nullable();
        $table->timestamp('verification_expires_at')->nullable();
        $table->boolean('verification_used')->default(false);
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compte', function (Blueprint $table) {
            //
        });
    }
};
