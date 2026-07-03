<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();

            // null = gasto externo del negocio (no asociado a un turno de caja)
            $table->foreignId('cierre_caja_id')->nullable()->constrained('cierres_caja')->nullOnDelete();

            // null si se usa texto libre (creación rápida de proveedor)
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->string('proveedor_nombre_libre')->nullable();

            $table->string('descripcion');
            $table->string('numero_factura')->nullable();
            $table->boolean('factura_pendiente')->default(true);

            $table->enum('tipo_pago', ['efectivo', 'tarjeta', 'transferencia', 'cheque']);
            $table->decimal('valor', 10, 2);
            $table->boolean('es_externo')->default(false);

            $table->foreignId('agregado_por')->constrained('users')->cascadeOnDelete();

            $table->timestamps();

            $table->index(['es_externo', 'factura_pendiente']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
