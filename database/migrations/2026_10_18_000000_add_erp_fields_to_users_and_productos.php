<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('perfil_empresarial', 20)->nullable();
        });
        Schema::table('productos', function (Blueprint $table) {
            $table->decimal('precio_venta_unitario', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('productos', fn (Blueprint $table) => $table->dropColumn('precio_venta_unitario'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('perfil_empresarial'));
    }
};
