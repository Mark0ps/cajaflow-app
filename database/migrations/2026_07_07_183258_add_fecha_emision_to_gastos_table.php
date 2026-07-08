<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->date('fecha_emision')->nullable()->after('cierre_caja_id');
        });

        // Backfill de datos reales: los gastos ya ligados a un cierre heredan
        // la fecha de ese cierre; los gastos externos existentes (sin cierre)
        // usan la fecha de creación como mejor aproximación disponible.
        DB::statement('
            UPDATE gastos
            INNER JOIN cierres_caja ON cierres_caja.id = gastos.cierre_caja_id
            SET gastos.fecha_emision = cierres_caja.fecha
            WHERE gastos.cierre_caja_id IS NOT NULL
        ');

        DB::statement('UPDATE gastos SET fecha_emision = DATE(created_at) WHERE fecha_emision IS NULL');

        DB::statement('ALTER TABLE gastos MODIFY fecha_emision DATE NOT NULL');

        Schema::table('gastos', function (Blueprint $table) {
            $table->index('fecha_emision');
        });
    }

    public function down(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->dropIndex(['fecha_emision']);
            $table->dropColumn('fecha_emision');
        });
    }
};
