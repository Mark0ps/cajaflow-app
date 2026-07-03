<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos_planilla', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->cascadeOnDelete();
            $table->date('fecha_pago');
            $table->decimal('monto_total', 10, 2);
            $table->enum('metodo', ['efectivo', 'transferencia', 'cheque']);

            // Foto/escaneo del formato firmado a mano
            $table->string('comprobante_path')->nullable();

            $table->foreignId('registrado_por')->constrained('users')->cascadeOnDelete();
            $table->text('notas')->nullable();

            $table->timestamps();

            $table->index(['empleado_id', 'fecha_pago']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos_planilla');
    }
};
