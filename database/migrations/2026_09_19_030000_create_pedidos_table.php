<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('cantidad');
            $table->string('tipo', 20);
            $table->string('estado', 20)->default('pendiente');
            $table->string('origen', 30);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
