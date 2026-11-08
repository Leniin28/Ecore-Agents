<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\Cliente;
use App\Models\Monedero;
use App\Models\MonederoMovimiento;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MonederoService
{
    /**
     * Obtiene o inicializa el monedero con saldo 0 para el cliente.
     */
    public function obtenerMonedero(Cliente $cliente): Monedero
    {
        return Monedero::firstOrCreate(
            ['cliente_id' => $cliente->id],
            ['saldo_centavos' => 0]
        );
    }

    /**
     * Recarga simulada de saldo en centavos enteros con protección de idempotencia.
     */
    public function recargar(Cliente $cliente, User $usuario, int $montoCentavos, string $concepto = 'Recarga demo de saldo', ?string $referencia = null): MonederoMovimiento
    {
        if ($montoCentavos <= 0) {
            throw ValidationException::withMessages(['monto' => 'El monto a recargar debe ser mayor a cero.']);
        }

        if ($montoCentavos > 100000000) { // Límite seguro demo de $1,000,000 MXN
            throw ValidationException::withMessages(['monto' => 'El monto supera el límite permitido para una recarga demo.']);
        }

        return DB::transaction(function () use ($cliente, $usuario, $montoCentavos, $concepto, $referencia) {
            $monedero = Monedero::query()->lockForUpdate()->firstOrCreate(
                ['cliente_id' => $cliente->id],
                ['saldo_centavos' => 0]
            );

            // Verificación de idempotencia por referencia de operación
            if ($referencia !== null && $referencia !== '') {
                $existente = MonederoMovimiento::query()->where('referencia', $referencia)->lockForUpdate()->first();
                if ($existente) {
                    if ($existente->monedero_id === $monedero->id
                        && $existente->tipo === MonederoMovimiento::TIPO_RECARGA
                        && $existente->monto_centavos === $montoCentavos) {
                        return $existente;
                    }

                    throw ValidationException::withMessages([
                        'referencia_operacion' => 'La referencia de operación ya fue procesada con datos incompatibles o por otro cliente.',
                    ]);
                }
            }

            $nuevoSaldo = $monedero->saldo_centavos + $montoCentavos;
            $monedero->update(['saldo_centavos' => $nuevoSaldo]);

            $refFinal = ($referencia !== null && $referencia !== '') ? $referencia : (string) Str::uuid();

            $movimiento = $monedero->movimientos()->create([
                'usuario_id' => $usuario->id,
                'referencia' => $refFinal,
                'tipo' => MonederoMovimiento::TIPO_RECARGA,
                'monto_centavos' => $montoCentavos,
                'saldo_centavos' => $nuevoSaldo,
                'concepto' => $concepto,
                'motivo' => 'Operación demostrativa aprobada por el usuario',
            ]);

            Auditoria::registrar(
                $usuario,
                'recarga_monedero_demo',
                'monedero',
                $monedero->id,
                "Recargó {$movimiento->monto_formateado} al monedero demo del cliente {$cliente->nombre}."
            );

            return $movimiento;
        });
    }

    /**
     * Ajuste administrativo auditado con motivo obligatorio e idempotencia.
     */
    public function ajustar(Monedero $monedero, User $admin, int $montoCentavos, string $motivo, string $concepto = 'Ajuste administrativo', ?string $referencia = null): MonederoMovimiento
    {
        $motivoLimpio = trim($motivo);
        if (mb_strlen($motivoLimpio) < 5) {
            throw ValidationException::withMessages(['motivo' => 'El motivo del ajuste es obligatorio y debe tener al menos 5 caracteres.']);
        }

        if ($montoCentavos === 0) {
            throw ValidationException::withMessages(['monto' => 'El monto del ajuste no puede ser cero.']);
        }

        return DB::transaction(function () use ($monedero, $admin, $montoCentavos, $motivoLimpio, $concepto, $referencia) {
            $monedero = Monedero::query()->lockForUpdate()->findOrFail($monedero->id);

            // Verificación de idempotencia por referencia de operación
            if ($referencia !== null && $referencia !== '') {
                $existente = MonederoMovimiento::query()->where('referencia', $referencia)->lockForUpdate()->first();
                if ($existente) {
                    if ($existente->monedero_id === $monedero->id
                        && $existente->tipo === MonederoMovimiento::TIPO_AJUSTE
                        && $existente->monto_centavos === $montoCentavos) {
                        return $existente;
                    }

                    throw ValidationException::withMessages([
                        'referencia_operacion' => 'La referencia de ajuste ya fue procesada con datos incompatibles.',
                    ]);
                }
            }

            $nuevoSaldo = $monedero->saldo_centavos + $montoCentavos;
            if ($nuevoSaldo < 0) {
                throw ValidationException::withMessages([
                    'monto' => "El ajuste dejaría el monedero en saldo negativo ({$monedero->saldo_formateado} actual).",
                ]);
            }

            $monedero->update(['saldo_centavos' => $nuevoSaldo]);
            $refFinal = ($referencia !== null && $referencia !== '') ? $referencia : (string) Str::uuid();

            $movimiento = $monedero->movimientos()->create([
                'usuario_id' => $admin->id,
                'referencia' => $refFinal,
                'tipo' => MonederoMovimiento::TIPO_AJUSTE,
                'monto_centavos' => $montoCentavos,
                'saldo_centavos' => $nuevoSaldo,
                'concepto' => $concepto,
                'motivo' => $motivoLimpio,
            ]);

            Auditoria::registrar(
                $admin,
                'ajuste_monedero',
                'monedero',
                $monedero->id,
                "Ajustó {$movimiento->monto_formateado} al monedero del cliente {$monedero->cliente->nombre}. Motivo: {$motivoLimpio}."
            );

            return $movimiento;
        });
    }

    /**
     * Cargo por compra en monedero dentro de la transacción de checkout.
     */
    public function debitarParaCompra(Monedero $monedero, User $usuario, int $montoCentavos, string $concepto, string $referencia): MonederoMovimiento
    {
        $monedero = Monedero::query()->lockForUpdate()->findOrFail($monedero->id);

        if ($monedero->saldo_centavos < $montoCentavos) {
            throw ValidationException::withMessages([
                'metodo' => "Saldo insuficiente en monedero. Saldo actual: {$monedero->saldo_formateado}. Total requerido: $".number_format($montoCentavos / 100, 2).' MXN.',
            ]);
        }

        $nuevoSaldo = $monedero->saldo_centavos - $montoCentavos;
        $monedero->update(['saldo_centavos' => $nuevoSaldo]);

        return $monedero->movimientos()->create([
            'usuario_id' => $usuario->id,
            'referencia' => $referencia,
            'tipo' => MonederoMovimiento::TIPO_PAGO,
            'monto_centavos' => -$montoCentavos,
            'saldo_centavos' => $nuevoSaldo,
            'concepto' => $concepto,
            'motivo' => 'Cargo por compra comercial en tienda demo',
        ]);
    }
}
