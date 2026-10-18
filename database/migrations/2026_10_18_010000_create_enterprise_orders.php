<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordenes_empresariales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->dateTime('fecha')->index();
            $table->string('estado', 20)->default('pendiente')->index();
            $table->decimal('total', 14, 2);
            $table->timestamps();
        });
        Schema::create('orden_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_empresarial_id')->constrained('ordenes_empresariales')->restrictOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario', 12, 2);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
            $table->unique(['orden_empresarial_id', 'producto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orden_detalles');
        Schema::dropIfExists('ordenes_empresariales');
    }
};
