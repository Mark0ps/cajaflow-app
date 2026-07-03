<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestamo_abonos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prestamo_id')->constrained('prestamos')->cascadeOnDelete();
            $table->foreignId('planilla_detalle_id')->nullable()
                ->constrained('planilla_detalles')->nullOnDelete();
            $table->decimal('monto', 10, 2);
            $table->date('fecha');
            $table->timestamps();

            $table->index('prestamo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamo_abonos');
    }
};
