<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procesos_internos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_empresarial_id')->constrained('ordenes_empresariales')->restrictOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 40);
            $table->string('descripcion');
            $table->string('estado', 20)->default('completado');
            $table->dateTime('fecha');
            $table->timestamps();
            $table->unique(['orden_empresarial_id', 'tipo']);
        });
        Schema::create('estado_erp', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('nivel', 20)->default('basico');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estado_erp');
        Schema::dropIfExists('procesos_internos');
    }
};
