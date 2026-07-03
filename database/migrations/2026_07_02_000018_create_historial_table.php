<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Tabla genérica de auditoría, mismo patrón que HistorialHelper en AutoSys.
    // No lleva FK constraints porque referencia distintas tablas (polimórfico simple).
    public function up(): void
    {
        Schema::create('historial', function (Blueprint $table) {
            $table->id();
            $table->string('tabla');            // ej: 'cierres_caja', 'gastos', 'planilla_detalles'
            $table->unsignedBigInteger('registro_id');
            $table->string('accion');           // creado, editado, eliminado, pago_aplicado, etc.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->json('datos_antes')->nullable();
            $table->json('datos_despues')->nullable();
            $table->timestamps();

            $table->index(['tabla', 'registro_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial');
    }
};
