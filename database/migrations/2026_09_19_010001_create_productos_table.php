<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('categoria');
            $table->unsignedInteger('stock_actual')->default(0);
            $table->unsignedInteger('stock_minimo')->default(0);
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->decimal('costo_unitario', 12, 2)->default(0);
            $table->string('estrategia_logistica', 10)->default('PULL');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
