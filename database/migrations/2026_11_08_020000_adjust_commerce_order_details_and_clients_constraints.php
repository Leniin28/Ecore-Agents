<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Permitir múltiples configuraciones del mismo producto en una misma orden
        // Eliminando la restricción única (orden_empresarial_id, producto_id) en orden_detalles.
        if (Schema::hasTable('orden_detalles')) {
            $indexes = collect(DB::select("PRAGMA index_list('orden_detalles')"))->pluck('name')->all();
            if (in_array('orden_detalles_orden_empresarial_id_producto_id_unique', $indexes, true)) {
                Schema::table('orden_detalles', function (Blueprint $table) {
                    $table->dropUnique('orden_detalles_orden_empresarial_id_producto_id_unique');
                });
            }
        }

        // 2. Permitir perfiles de cliente independientes conservando el correo auténtico
        // Eliminando la restricción única global sobre clientes.correo.
        // La restricción única sobre clientes.user_id se mantiene garantizando un cliente por usuario.
        if (Schema::hasTable('clientes')) {
            $indexes = collect(DB::select("PRAGMA index_list('clientes')"))->pluck('name')->all();
            if (in_array('clientes_correo_unique', $indexes, true)) {
                Schema::table('clientes', function (Blueprint $table) {
                    $table->dropUnique('clientes_correo_unique');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orden_detalles')) {
            Schema::table('orden_detalles', function (Blueprint $table) {
                $table->unique(['orden_empresarial_id', 'producto_id']);
            });
        }

        if (Schema::hasTable('clientes')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->unique('correo');
            });
        }
    }
};
