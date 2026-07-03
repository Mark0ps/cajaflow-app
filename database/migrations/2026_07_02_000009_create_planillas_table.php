<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planillas', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('mes');
            $table->unsignedTinyInteger('quincena'); // 1 o 2
            $table->date('periodo_inicio');
            $table->date('periodo_fin');

            // "cerrada" = el cálculo ya no se edita; NO implica que ya se pagó
            $table->enum('estado', ['borrador', 'cerrada'])->default('borrador');

            $table->foreignId('generada_por')->constrained('users')->cascadeOnDelete();
            $table->timestamp('cerrada_en')->nullable();

            $table->timestamps();

            $table->unique(['anio', 'mes', 'quincena']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planillas');
    }
};
