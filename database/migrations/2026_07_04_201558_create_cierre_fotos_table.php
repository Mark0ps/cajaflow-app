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
        Schema::create('cierre_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cierre_caja_id')->constrained('cierres_caja')->cascadeOnDelete();
            $table->string('foto_path');
            $table->string('descripcion')->nullable();
            $table->foreignId('subido_por')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('cierre_caja_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cierre_fotos');
    }
};
