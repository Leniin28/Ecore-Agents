<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes_empresariales', function (Blueprint $table) {
            $table->dateTime('procesada_at')->nullable()->index();
        });
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->foreignId('orden_detalle_id')->nullable()->unique()->constrained('orden_detalles')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->dropForeign(['orden_detalle_id']);
            $table->dropUnique(['orden_detalle_id']);
            $table->dropColumn('orden_detalle_id');
        });
        Schema::table('ordenes_empresariales', fn (Blueprint $table) => $table->dropColumn('procesada_at'));
    }
};
