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
    Schema::create('email_verification_codes', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('compte_id')->index(); // lien vers la table compte
        $table->string('code', 6);
        $table->timestamp('expires_at');
        $table->boolean('used')->default(false);
        $table->timestamps();

        $table->foreign('compte_id')->references('id')->on('compte')->onDelete('cascade');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_verification_codes');
    }
};
