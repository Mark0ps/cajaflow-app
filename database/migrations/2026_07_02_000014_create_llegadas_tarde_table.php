<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('llegadas_tarde', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->date('fecha');
            $table->unsignedSmallInteger('minutos_tarde');
            $table->decimal('valor_deduccion', 10, 2)->default(0);
            $table->foreignId('planilla_detalle_id')->nullable()
                ->constrained('planilla_detalles')->nullOnDelete();
            $table->timestamps();

            $table->index(['empleado_id', 'planilla_detalle_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('llegadas_tarde');
    }
};
