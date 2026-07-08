<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bug encontrado en la auditoría 2026-07-08: al volver nullable
 * cierre_caja_id (para vales libres y gastos externos), las FK se recrearon
 * con nullOnDelete(). Eso contradice el diseño documentado de eliminar cierre
 * ("gastos/vales del cierre se borran solos por la FK"): al eliminar un
 * cierre, sus vales quedaban huérfanos como falsos "vales libres" (se seguían
 * descontando en la siguiente planilla) y sus gastos como fantasmas que
 * siguen sumando en reportes. Nullable + cascade conviven bien: la cascada
 * solo alcanza filas ligadas al cierre eliminado, las NULL no se tocan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vales', function (Blueprint $table) {
            $table->dropForeign(['cierre_caja_id']);
        });
        Schema::table('vales', function (Blueprint $table) {
            $table->foreign('cierre_caja_id')->references('id')->on('cierres_caja')->cascadeOnDelete();
        });

        Schema::table('gastos', function (Blueprint $table) {
            $table->dropForeign(['cierre_caja_id']);
        });
        Schema::table('gastos', function (Blueprint $table) {
            $table->foreign('cierre_caja_id')->references('id')->on('cierres_caja')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vales', function (Blueprint $table) {
            $table->dropForeign(['cierre_caja_id']);
        });
        Schema::table('vales', function (Blueprint $table) {
            $table->foreign('cierre_caja_id')->references('id')->on('cierres_caja')->nullOnDelete();
        });

        Schema::table('gastos', function (Blueprint $table) {
            $table->dropForeign(['cierre_caja_id']);
        });
        Schema::table('gastos', function (Blueprint $table) {
            $table->foreign('cierre_caja_id')->references('id')->on('cierres_caja')->nullOnDelete();
        });
    }
};
