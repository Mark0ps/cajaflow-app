<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_efectivo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cierre_caja_id')->constrained('cierres_caja')->cascadeOnDelete();
            $table->enum('tipo', ['entrada', 'salida']);
            $table->decimal('monto', 10, 2);
            $table->string('motivo');
            $table->foreignId('registrado_por')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::table('cierres_caja', function (Blueprint $table) {
            $table->decimal('total_entradas', 10, 2)->default(0)->after('total_vales');
            $table->decimal('total_salidas', 10, 2)->default(0)->after('total_entradas');
        });
    }

    public function down(): void
    {
        Schema::table('cierres_caja', function (Blueprint $table) {
            $table->dropColumn(['total_entradas', 'total_salidas']);
        });

        Schema::dropIfExists('movimientos_efectivo');
    }
};
