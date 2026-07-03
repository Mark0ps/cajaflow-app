<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cierres_caja', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->enum('turno', ['matutino', 'tarde', 'nocturno']);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('monto_inicial', 10, 2)->default(0);

            $table->decimal('efectivo', 10, 2)->default(0);
            $table->decimal('tarjeta_credito', 10, 2)->default(0);
            $table->decimal('transferencia', 10, 2)->default(0);
            $table->decimal('total_ingreso', 10, 2)->default(0);

            $table->decimal('venta_sistema_a2', 10, 2)->nullable();
            $table->decimal('diferencia', 10, 2)->default(0);

            $table->decimal('total_gastos', 10, 2)->default(0);
            $table->decimal('total_vales', 10, 2)->default(0);
            $table->decimal('efectivo_dia_venta', 10, 2)->default(0);

            $table->enum('estado', ['abierto', 'cerrado', 'revisado_secretaria'])->default('abierto');
            $table->text('observaciones')->nullable();

            $table->foreignId('revisado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revisado_en')->nullable();

            $table->timestamps();

            $table->unique(['fecha', 'turno', 'user_id']);
            $table->index(['fecha', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cierres_caja');
    }
};
