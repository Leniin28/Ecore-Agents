<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_customers(): void
    {
        $this->get(route('clientes.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_list_customers(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();

        $this->actingAs($user)
            ->get(route('clientes.index'))
            ->assertOk()
            ->assertSee($cliente->nombre)
            ->assertSee($cliente->correo);
    }

    public function test_authenticated_user_can_create_customer(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('clientes.store'), $this->validData());

        $cliente = Cliente::query()->where('correo', 'cliente.qa@example.com')->firstOrFail();

        $response->assertRedirect(route('clientes.show', $cliente));
        $this->assertSame(now()->toDateString(), $cliente->fecha_registro->format('Y-m-d'));
        $this->assertDatabaseHas('clientes', [
            'correo' => 'cliente.qa@example.com',
            'estado' => Cliente::ESTADO_ACTIVO,
        ]);
    }

    public function test_invalid_customer_data_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('clientes.store'), [
                'nombre' => '',
                'correo' => 'correo-invalido',
                'telefono' => '',
                'estado' => 'pendiente',
            ])
            ->assertSessionHasErrors(['nombre', 'correo', 'telefono', 'estado']);

        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_duplicate_customer_email_is_rejected(): void
    {
        $user = User::factory()->create();
        Cliente::factory()->create(['correo' => 'duplicado@example.com']);

        $this->actingAs($user)
            ->post(route('clientes.store'), $this->validData(['correo' => 'duplicado@example.com']))
            ->assertSessionHasErrors([
                'correo' => 'Ya existe un cliente con este correo.',
            ]);
    }

    public function test_authenticated_user_can_view_customer_detail(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();

        $this->actingAs($user)
            ->get(route('clientes.show', $cliente))
            ->assertOk()
            ->assertSee($cliente->nombre)
            ->assertSee('Historial de interacciones')
            ->assertSee('Registrar interacción');
    }

    public function test_authenticated_user_can_update_customer_and_keep_own_email(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create(['correo' => 'cliente@example.com']);

        $this->actingAs($user)
            ->put(route('clientes.update', $cliente), $this->validData([
                'nombre' => 'Cliente Actualizado',
                'correo' => 'cliente@example.com',
                'estado' => Cliente::ESTADO_INACTIVO,
            ]))
            ->assertRedirect(route('clientes.show', $cliente));

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'nombre' => 'Cliente Actualizado',
            'correo' => 'cliente@example.com',
            'estado' => Cliente::ESTADO_INACTIVO,
        ]);
    }

    public function test_customer_search_matches_name_email_or_company(): void
    {
        $user = User::factory()->create();
        $matching = Cliente::factory()->create(['empresa' => 'Estudio Horizonte']);
        $other = Cliente::factory()->create(['empresa' => 'Comercial Delta']);

        $this->actingAs($user)
            ->get(route('clientes.index', ['buscar' => 'Horizonte']))
            ->assertOk()
            ->assertSee($matching->nombre)
            ->assertDontSee($other->nombre)
            ->assertSee('value="Horizonte"', false);
    }

    public function test_customer_status_filter_returns_only_selected_status(): void
    {
        $user = User::factory()->create();
        $active = Cliente::factory()->create(['estado' => Cliente::ESTADO_ACTIVO]);
        $inactive = Cliente::factory()->inactivo()->create();

        $this->actingAs($user)
            ->get(route('clientes.index', ['estado' => Cliente::ESTADO_INACTIVO]))
            ->assertOk()
            ->assertSee($inactive->nombre)
            ->assertDontSee($active->nombre);
    }

    public function test_regular_user_cannot_delete_customer(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();

        $this->actingAs($user)
            ->get(route('clientes.index'))
            ->assertOk()
            ->assertDontSee('Eliminar');

        $this->actingAs($user)
            ->delete(route('clientes.destroy', $cliente))
            ->assertForbidden();

        $this->assertModelExists($cliente);
    }

    public function test_admin_can_delete_customer(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $cliente = Cliente::factory()->create();

        $this->actingAs($admin)
            ->delete(route('clientes.destroy', $cliente))
            ->assertRedirect(route('clientes.index'));

        $this->assertModelMissing($cliente);
    }

    public function test_guest_cannot_access_customer_api(): void
    {
        $this->getJson(route('api.clientes.index'))->assertUnauthorized();
    }

    public function test_customer_api_lists_customers(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();

        $this->actingAs($user)
            ->getJson(route('api.clientes.index'))
            ->assertOk()
            ->assertJsonPath('data.0.id', $cliente->id)
            ->assertJsonPath('data.0.correo', $cliente->correo)
            ->assertJsonPath('data.0.etapa_crm', Cliente::ETAPA_PROSPECTO);
    }

    public function test_customer_api_creates_customer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('api.clientes.store'), $this->validData())
            ->assertCreated()
            ->assertJsonPath('data.correo', 'cliente.qa@example.com')
            ->assertJsonPath('data.fecha_registro', now()->toDateString())
            ->assertJsonPath('data.etapa_crm', Cliente::ETAPA_PROSPECTO);

        $this->assertDatabaseHas('clientes', ['correo' => 'cliente.qa@example.com']);
    }

    public function test_customer_api_rejects_invalid_data(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('api.clientes.store'), [
                'nombre' => '',
                'correo' => 'correo-invalido',
                'telefono' => '',
                'estado' => 'pendiente',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre', 'correo', 'telefono', 'estado']);

        $this->assertDatabaseCount('clientes', 0);
    }

    public function test_customer_api_shows_customer(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();

        $this->actingAs($user)
            ->getJson(route('api.clientes.show', $cliente))
            ->assertOk()
            ->assertJsonPath('data.id', $cliente->id)
            ->assertJsonPath('data.nombre', $cliente->nombre);
    }

    public function test_customer_api_updates_customer(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();

        $this->actingAs($user)
            ->putJson(route('api.clientes.update', $cliente), $this->validData([
                'nombre' => 'API Actualizado',
                'correo' => $cliente->correo,
            ]))
            ->assertOk()
            ->assertJsonPath('data.nombre', 'API Actualizado');

        $this->assertDatabaseHas('clientes', ['id' => $cliente->id, 'nombre' => 'API Actualizado']);
    }

    public function test_regular_user_cannot_delete_customer_through_api(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('api.clientes.destroy', $cliente))
            ->assertForbidden();

        $this->assertModelExists($cliente);
    }

    public function test_admin_can_delete_customer_through_api(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $cliente = Cliente::factory()->create();

        $this->actingAs($admin)
            ->deleteJson(route('api.clientes.destroy', $cliente))
            ->assertNoContent();

        $this->assertModelMissing($cliente);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validData(array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'Cliente QA',
            'correo' => 'cliente.qa@example.com',
            'telefono' => '5512345678',
            'empresa' => 'Empresa QA',
            'estado' => Cliente::ESTADO_ACTIVO,
        ], $overrides);
    }
}
