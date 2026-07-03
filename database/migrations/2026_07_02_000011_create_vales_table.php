<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cierre_caja_id')->constrained('cierres_caja')->cascadeOnDelete();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->decimal('monto', 10, 2);
            $table->string('descripcion')->nullable();

            $table->boolean('aplicado_en_planilla')->default(false);
            $table->foreignId('planilla_detalle_id')->nullable()
                ->constrained('planilla_detalles')->nullOnDelete();

            $table->timestamps();

            $table->index(['empleado_id', 'aplicado_en_planilla']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vales');
    }
};
