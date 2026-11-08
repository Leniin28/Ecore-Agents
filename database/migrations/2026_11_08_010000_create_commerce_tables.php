<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carritos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->unique()->constrained('clientes')->restrictOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });

        Schema::create('carrito_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrito_id')->constrained('carritos')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->json('configuracion')->nullable();
            $table->string('configuracion_hash', 64)->default('');
            $table->timestamps();
            $table->unique(['carrito_id', 'producto_id', 'configuracion_hash']);
        });

        Schema::create('promociones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('tipo_descuento', 20); // 'porcentaje' o 'monto_fijo'
            $table->unsignedBigInteger('valor'); // porcentaje: puntos base (1000 = 10%), monto_fijo: centavos
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->unsignedInteger('limite_usos')->nullable();
            $table->unsignedInteger('usos_actuales')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('promocion_producto', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promocion_id')->constrained('promociones')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->unique(['promocion_id', 'producto_id']);
        });

        Schema::create('impuestos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->unsignedInteger('puntos_base'); // 1600 = 16.00%
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('monederos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->unique()->constrained('clientes')->restrictOnDelete();
            $table->unsignedBigInteger('saldo_centavos')->default(0);
            $table->timestamps();
        });

        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_empresarial_id')->unique()->constrained('ordenes_empresariales')->restrictOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->uuid('referencia')->unique();
            $table->string('metodo', 30);
            $table->string('estado', 20);
            $table->unsignedBigInteger('subtotal_centavos');
            $table->unsignedBigInteger('descuento_centavos')->default(0);
            $table->unsignedBigInteger('base_gravable_centavos')->default(0);
            $table->unsignedBigInteger('impuesto_centavos')->default(0);
            $table->unsignedBigInteger('total_centavos');
            $table->foreignId('promocion_id')->nullable()->constrained('promociones')->nullOnDelete();
            $table->json('desglose');
            $table->json('datos_entrega');
            $table->timestamps();
        });

        Schema::create('monedero_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monedero_id')->constrained('monederos')->restrictOnDelete();
            $table->foreignId('pago_id')->nullable()->unique()->constrained('pagos')->nullOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->uuid('referencia')->unique();
            $table->string('tipo', 30); // 'recarga_demo', 'pago_compra', 'ajuste_admin'
            $table->bigInteger('monto_centavos');
            $table->unsignedBigInteger('saldo_centavos');
            $table->string('concepto');
            $table->text('motivo')->nullable();
            $table->timestamps();
        });

        Schema::create('tickets_atencion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->restrictOnDelete();
            $table->foreignId('orden_empresarial_id')->nullable()->constrained('ordenes_empresariales')->nullOnDelete();
            $table->string('asunto');
            $table->text('mensaje');
            $table->string('estado', 20)->default('abierto');
            $table->text('respuesta')->nullable();
            $table->foreignId('respondido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('respondido_at')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('orden_detalles') && ! Schema::hasColumn('orden_detalles', 'configuracion')) {
            Schema::table('orden_detalles', function (Blueprint $table) {
                $table->json('configuracion')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orden_detalles') && Schema::hasColumn('orden_detalles', 'configuracion')) {
            Schema::table('orden_detalles', function (Blueprint $table) {
                $table->dropColumn('configuracion');
            });
        }

        foreach ([
            'tickets_atencion',
            'monedero_movimientos',
            'pagos',
            'monederos',
            'impuestos',
            'promocion_producto',
            'promociones',
            'carrito_detalles',
            'carritos',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
