<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pago_planilla_detalle', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pago_planilla_id')->constrained('pagos_planilla')->cascadeOnDelete();
            $table->foreignId('planilla_detalle_id')->constrained('planilla_detalles')->cascadeOnDelete();
            $table->decimal('monto_aplicado', 10, 2);
            $table->timestamps();

            $table->unique(['pago_planilla_id', 'planilla_detalle_id'], 'pago_planilla_detalle_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pago_planilla_detalle');
    }
};
