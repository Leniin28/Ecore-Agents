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
     * Recarga simulada de saldo en centavos enteros.
     */
    public function recargar(Cliente $cliente, User $usuario, int $montoCentavos, string $concepto = 'Recarga demo de saldo'): MonederoMovimiento
    {
        if ($montoCentavos <= 0) {
            throw ValidationException::withMessages(['monto' => 'El monto a recargar debe ser mayor a cero.']);
        }

        if ($montoCentavos > 100000000) { // Límite seguro demo de $1,000,000 MXN
            throw ValidationException::withMessages(['monto' => 'El monto supera el límite permitido para una recarga demo.']);
        }

        return DB::transaction(function () use ($cliente, $usuario, $montoCentavos, $concepto) {
            $monedero = Monedero::query()->lockForUpdate()->firstOrCreate(
                ['cliente_id' => $cliente->id],
                ['saldo_centavos' => 0]
            );

            $nuevoSaldo = $monedero->saldo_centavos + $montoCentavos;
            $monedero->update(['saldo_centavos' => $nuevoSaldo]);

            $referencia = (string) Str::uuid();

            $movimiento = $monedero->movimientos()->create([
                'usuario_id' => $usuario->id,
                'referencia' => $referencia,
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
     * Ajuste administrativo auditado con motivo obligatorio.
     */
    public function ajustar(Monedero $monedero, User $admin, int $montoCentavos, string $motivo, string $concepto = 'Ajuste administrativo'): MonederoMovimiento
    {
        $motivoLimpio = trim($motivo);
        if (mb_strlen($motivoLimpio) < 5) {
            throw ValidationException::withMessages(['motivo' => 'El motivo del ajuste es obligatorio y debe tener al menos 5 caracteres.']);
        }

        if ($montoCentavos === 0) {
            throw ValidationException::withMessages(['monto' => 'El monto del ajuste no puede ser cero.']);
        }

        return DB::transaction(function () use ($monedero, $admin, $montoCentavos, $motivoLimpio, $concepto) {
            $monedero = Monedero::query()->lockForUpdate()->findOrFail($monedero->id);

            $nuevoSaldo = $monedero->saldo_centavos + $montoCentavos;
            if ($nuevoSaldo < 0) {
                throw ValidationException::withMessages([
                    'monto' => "El ajuste dejaría el monedero en saldo negativo ({$monedero->saldo_formateado} actual).",
                ]);
            }

            $monedero->update(['saldo_centavos' => $nuevoSaldo]);
            $referencia = (string) Str::uuid();

            $movimiento = $monedero->movimientos()->create([
                'usuario_id' => $admin->id,
                'referencia' => $referencia,
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
