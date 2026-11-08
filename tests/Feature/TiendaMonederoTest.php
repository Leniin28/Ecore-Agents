<?php

namespace Tests\Feature;

use App\Models\Monedero;
use App\Models\User;
use App\Services\MonederoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TiendaMonederoTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_wallet_starts_with_zero_balance(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $service = app(MonederoService::class);

        $monedero = $service->obtenerMonedero($user->obtenerCliente());

        $this->assertSame(0, $monedero->saldo_centavos);
        $this->assertSame('$0.00 MXN', $monedero->saldo_formateado);
    }

    public function test_customer_can_perform_simulated_top_up(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        $response = $this->actingAs($user)
            ->post(route('monedero.recargar'), [
                'monto_pesos' => 500, // $500.00 MXN = 50,000 centavos
            ]);

        $response->assertRedirect(route('monedero.index'));

        $monedero = $user->obtenerCliente()->monedero->fresh();
        $this->assertSame(50000, $monedero->saldo_centavos);
        $this->assertSame('$500.00 MXN', $monedero->saldo_formateado);

        $this->assertDatabaseHas('monedero_movimientos', [
            'monedero_id' => $monedero->id,
            'tipo' => 'recarga_demo',
            'monto_centavos' => 50000,
            'saldo_centavos' => 50000,
        ]);
    }

    public function test_admin_can_adjust_wallet_balance_with_mandatory_motive_and_audit(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $clientUser = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        $service = app(MonederoService::class);
        $monedero = $service->obtenerMonedero($clientUser->obtenerCliente());

        // Ajuste a favor: +$250.00 MXN
        $response = $this->actingAs($admin)
            ->post(route('admin.tienda.monederos.ajustar', $monedero), [
                'tipo_ajuste' => 'credito',
                'monto_pesos' => 250.00,
                'motivo' => 'Compensación por demostración académica autorizada',
            ]);

        $response->assertRedirect(route('admin.tienda.monederos.show', $monedero));

        $this->assertSame(25000, $monedero->fresh()->saldo_centavos);

        $this->assertDatabaseHas('monedero_movimientos', [
            'monedero_id' => $monedero->id,
            'tipo' => 'ajuste_admin',
            'monto_centavos' => 25000,
            'saldo_centavos' => 25000,
            'motivo' => 'Compensación por demostración académica autorizada',
        ]);

        $this->assertDatabaseHas('auditorias', [
            'usuario_id' => $admin->id,
            'accion' => 'ajuste_monedero',
        ]);
    }

    public function test_adjustment_cannot_leave_wallet_in_negative_balance(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $clientUser = User::factory()->create(['role' => User::ROLE_CLIENTE]);

        $service = app(MonederoService::class);
        $cliente = $clientUser->obtenerCliente();
        $monedero = $service->obtenerMonedero($cliente);
        $service->recargar($cliente, $clientUser, 10000); // $100.00 MXN

        // Intentar deducir $200.00 cuando solo hay $100.00
        $this->actingAs($admin)
            ->post(route('admin.tienda.monederos.ajustar', $monedero), [
                'tipo_ajuste' => 'debito',
                'monto_pesos' => 200.00,
                'motivo' => 'Deducción excesiva de prueba',
            ])
            ->assertSessionHasErrors('monto');

        // Saldo sigue intacto en 10,000 centavos ($100.00)
        $this->assertSame(10000, $monedero->fresh()->saldo_centavos);
    }

    public function test_client_cannot_access_admin_wallet_management(): void
    {
        $clientUser = User::factory()->create(['role' => User::ROLE_CLIENTE]);
        $service = app(MonederoService::class);
        $monedero = $service->obtenerMonedero($clientUser->obtenerCliente());

        $this->actingAs($clientUser)
            ->get(route('admin.tienda.monederos.index'))
            ->assertForbidden();

        $this->actingAs($clientUser)
            ->post(route('admin.tienda.monederos.ajustar', $monedero), [
                'tipo_ajuste' => 'credito',
                'monto_pesos' => 1000,
                'motivo' => 'Auto-crédito indebido',
            ])
            ->assertForbidden();
    }
}
