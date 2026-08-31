<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->enum('etapa_crm', ['prospecto', 'activo', 'frecuente', 'inactivo'])
                ->default('prospecto')
                ->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('etapa_crm');
        });
    }
};
