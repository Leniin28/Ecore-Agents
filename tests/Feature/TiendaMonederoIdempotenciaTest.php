<?php

namespace Tests\Feature;

use App\Models\Monedero;
use App\Models\User;
use App\Services\MonederoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TiendaMonederoIdempotenciaTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_top_up_submission_twice_produces_only_one_movement_and_one_balance_increment(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $referenciaOperacion = (string) Str::uuid();

        // Primer envío: Recarga de $500.00 MXN (50,000 centavos)
        $this->actingAs($user)
            ->post(route('monedero.recargar'), [
                'monto_pesos' => 500,
                'referencia_operacion' => $referenciaOperacion,
            ])
            ->assertRedirect(route('monedero.index'));

        $monedero = $user->obtenerCliente()->monedero->fresh();
        $this->assertSame(50000, $monedero->saldo_centavos);
        $this->assertDatabaseCount('monedero_movimientos', 1);

        // Segundo envío idéntico con la misma referencia (ej. doble clic o recarga de página)
        $this->actingAs($user)
            ->post(route('monedero.recargar'), [
                'monto_pesos' => 500,
                'referencia_operacion' => $referenciaOperacion,
            ])
            ->assertRedirect(route('monedero.index'));

        // Saldo y conteo de movimientos permanecen intactos (idempotente)
        $monedero->refresh();
        $this->assertSame(50000, $monedero->saldo_centavos);
        $this->assertDatabaseCount('monedero_movimientos', 1);

        // No se duplican auditorías por el reintento
        $this->assertDatabaseCount('auditorias', 1);
    }

    public function test_reusing_same_reference_with_different_amount_is_rejected(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $referenciaOperacion = (string) Str::uuid();

        // Primer envío de $500.00 MXN
        $this->actingAs($user)->post(route('monedero.recargar'), [
            'monto_pesos' => 500,
            'referencia_operacion' => $referenciaOperacion,
        ]);

        // Reintento usando la misma referencia pero con monto incompatible ($1,000.00 MXN)
        $response = $this->actingAs($user)->post(route('monedero.recargar'), [
            'monto_pesos' => 1000,
            'referencia_operacion' => $referenciaOperacion,
        ]);

        $response->assertSessionHasErrors('referencia_operacion');

        // Saldo permanece en $500.00
        $this->assertSame(50000, $user->obtenerCliente()->monedero->fresh()->saldo_centavos);
        $this->assertDatabaseCount('monedero_movimientos', 1);
    }

    public function test_reusing_same_reference_from_another_user_is_rejected(): void
    {
        $userA = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $userB = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $referenciaOperacion = (string) Str::uuid();

        // User A recarga $200.00 MXN
        $this->actingAs($userA)->post(route('monedero.recargar'), [
            'monto_pesos' => 200,
            'referencia_operacion' => $referenciaOperacion,
        ]);

        // User B intenta utilizar la misma referencia de User A
        $response = $this->actingAs($userB)->post(route('monedero.recargar'), [
            'monto_pesos' => 200,
            'referencia_operacion' => $referenciaOperacion,
        ]);

        $response->assertSessionHasErrors('referencia_operacion');

        // Monedero de User B sigue en cero
        $monederoB = app(MonederoService::class)->obtenerMonedero($userB->obtenerCliente());
        $this->assertSame(0, $monederoB->saldo_centavos);
    }

    public function test_two_distinct_top_ups_with_different_references_succeed_normally(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        // Recarga 1: $100.00 MXN
        $this->actingAs($user)->post(route('monedero.recargar'), [
            'monto_pesos' => 100,
            'referencia_operacion' => (string) Str::uuid(),
        ]);

        // Recarga 2: $300.00 MXN
        $this->actingAs($user)->post(route('monedero.recargar'), [
            'monto_pesos' => 300,
            'referencia_operacion' => (string) Str::uuid(),
        ]);

        $monedero = $user->obtenerCliente()->monedero->fresh();
        // Saldo total: $400.00 MXN = 40,000 centavos
        $this->assertSame(40000, $monedero->saldo_centavos);
        $this->assertCount(2, $monedero->movimientos);
    }

    public function test_admin_adjustment_is_protected_against_duplicate_replay(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $clientUser = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $monedero = app(MonederoService::class)->obtenerMonedero($clientUser->obtenerCliente());

        $refAjuste = (string) Str::uuid();

        // Ajuste 1: Crédito de $150.00 MXN
        $this->actingAs($admin)->post(route('admin.tienda.monederos.ajustar', $monedero), [
            'tipo_ajuste' => 'credito',
            'monto_pesos' => 150.00,
            'motivo' => 'Bono de bienvenida institucional',
            'referencia_operacion' => $refAjuste,
        ]);

        $this->assertSame(15000, $monedero->fresh()->saldo_centavos);
        $this->assertDatabaseCount('monedero_movimientos', 1);

        // Reintento idéntico del ajuste administrativo con la misma referencia
        $this->actingAs($admin)->post(route('admin.tienda.monederos.ajustar', $monedero), [
            'tipo_ajuste' => 'credito',
            'monto_pesos' => 150.00,
            'motivo' => 'Bono de bienvenida institucional',
            'referencia_operacion' => $refAjuste,
        ]);

        // Saldo y movimientos intactos
        $this->assertSame(15000, $monedero->fresh()->saldo_centavos);
        $this->assertDatabaseCount('monedero_movimientos', 1);
        $this->assertDatabaseCount('auditorias', 1);
    }
}
