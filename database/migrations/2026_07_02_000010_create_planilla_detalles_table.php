<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planilla_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planilla_id')->constrained('planillas')->cascadeOnDelete();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();

            $table->decimal('sueldo_quincenal', 10, 2);
            $table->decimal('sueldo_diario', 10, 2);
            $table->unsignedTinyInteger('dias_laborados')->default(15);
            $table->decimal('horas_extras_valor', 10, 2)->default(0);
            $table->decimal('salario_devengado', 10, 2);

            $table->decimal('total_vales', 10, 2)->default(0);
            $table->decimal('total_compras_tienda', 10, 2)->default(0);
            $table->decimal('total_abono_prestamo', 10, 2)->default(0);
            $table->decimal('total_llegadas_tarde', 10, 2)->default(0);
            $table->decimal('otras_deducciones', 10, 2)->default(0);
            $table->decimal('total_deducciones', 10, 2)->default(0);
            $table->decimal('total_a_pagar', 10, 2)->default(0);

            // Pago desacoplado del cálculo: ver pagos_planilla / pago_planilla_detalle
            $table->decimal('monto_pagado', 10, 2)->default(0);
            $table->decimal('saldo_pendiente', 10, 2)->default(0);
            $table->enum('estado_pago', ['pendiente', 'parcial', 'pagado'])->default('pendiente');

            $table->timestamps();

            $table->unique(['planilla_id', 'empleado_id']);
            $table->index(['empleado_id', 'estado_pago']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planilla_detalles');
    }
};
