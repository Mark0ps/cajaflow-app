<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vales', function (Blueprint $table) {
            $table->date('fecha_emision')->nullable()->after('cierre_caja_id');
            $table->string('comprobante_path')->nullable()->after('descripcion');
            $table->foreignId('registrado_por')->nullable()->after('comprobante_path')
                ->constrained('users')->cascadeOnDelete();
        });

        // Backfill de datos reales: los vales existentes ya ligados a un
        // cierre heredan su fecha_emision y registrado_por de ese cierre.
        DB::statement('
            UPDATE vales
            INNER JOIN cierres_caja ON cierres_caja.id = vales.cierre_caja_id
            SET vales.fecha_emision = cierres_caja.fecha,
                vales.registrado_por = cierres_caja.user_id
            WHERE vales.cierre_caja_id IS NOT NULL
        ');

        // No debería quedar ningún vale sin cierre en este punto (el vale
        // libre nace con esta misma migración), pero por si acaso: usa la
        // fecha de creación del registro como último recurso.
        DB::statement('UPDATE vales SET fecha_emision = DATE(created_at) WHERE fecha_emision IS NULL');

        DB::statement('ALTER TABLE vales MODIFY fecha_emision DATE NOT NULL');
        DB::statement('ALTER TABLE vales MODIFY registrado_por BIGINT UNSIGNED NOT NULL');

        Schema::table('vales', function (Blueprint $table) {
            $table->dropForeign(['cierre_caja_id']);
        });

        DB::statement('ALTER TABLE vales MODIFY cierre_caja_id BIGINT UNSIGNED NULL');

        Schema::table('vales', function (Blueprint $table) {
            $table->foreign('cierre_caja_id')->references('id')->on('cierres_caja')->nullOnDelete();
            $table->index('fecha_emision');
        });
    }

    public function down(): void
    {
        Schema::table('vales', function (Blueprint $table) {
            $table->dropIndex(['fecha_emision']);
            $table->dropForeign(['cierre_caja_id']);
        });

        DB::statement('ALTER TABLE vales MODIFY cierre_caja_id BIGINT UNSIGNED NOT NULL');

        Schema::table('vales', function (Blueprint $table) {
            $table->foreign('cierre_caja_id')->references('id')->on('cierres_caja')->cascadeOnDelete();
        });

        Schema::table('vales', function (Blueprint $table) {
            $table->dropForeign(['registrado_por']);
            $table->dropColumn(['fecha_emision', 'comprobante_path', 'registrado_por']);
        });
    }
};
