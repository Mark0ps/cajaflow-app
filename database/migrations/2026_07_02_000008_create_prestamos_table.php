<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prestamos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->decimal('monto_original', 10, 2);
            $table->decimal('saldo_pendiente', 10, 2);
            $table->date('fecha_otorgado');
            $table->string('motivo')->nullable();

            $table->enum('metodo_cobro', ['quincenal', 'mensual'])->default('quincenal');
            $table->decimal('monto_cuota', 10, 2);

            $table->enum('estado', ['activo', 'pagado'])->default('activo');
            $table->timestamps();

            $table->index(['empleado_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};
