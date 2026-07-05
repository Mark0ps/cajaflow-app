<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prestamo_abonos', function (Blueprint $table) {
            $table->string('motivo')->nullable()->after('monto');
        });
    }

    public function down(): void
    {
        Schema::table('prestamo_abonos', function (Blueprint $table) {
            $table->dropColumn('motivo');
        });
    }
};
