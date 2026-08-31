<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Interaccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InteraccionCrmStageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_interaction_history(): void
    {
        $cliente = Cliente::factory()->create();

        $this->get(route('clientes.interacciones.index', $cliente))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_interaction_history(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();
        $interaccion = Interaccion::factory()->create([
            'cliente_id' => $cliente->id,
            'usuario_id' => $user->id,
            'descripcion' => 'Seguimiento visible para el cliente.',
        ]);

        $this->actingAs($user)
            ->get(route('clientes.interacciones.index', $cliente))
            ->assertOk()
            ->assertSee($interaccion->descripcion)
            ->assertSee($user->name);
    }

    public function test_authenticated_user_can_register_each_interaction_type(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();

        foreach (array_keys(Interaccion::tipos()) as $tipo) {
            $this->actingAs($user)
                ->post(route('interacciones.store'), $this->interactionData($cliente, ['tipo' => $tipo]))
                ->assertRedirect(route('clientes.show', $cliente))
                ->assertSessionHas('success');

            $this->assertDatabaseHas('interacciones', [
                'cliente_id' => $cliente->id,
                'usuario_id' => $user->id,
                'tipo' => $tipo,
            ]);
        }
    }

    public function test_invalid_interaction_type_is_rejected(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();

        $this->actingAs($user)
            ->post(route('interacciones.store'), $this->interactionData($cliente, ['tipo' => 'mensaje']))
            ->assertSessionHasErrors('tipo');

        $this->assertDatabaseCount('interacciones', 0);
    }

    public function test_interaction_description_is_required(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();

        $this->actingAs($user)
            ->post(route('interacciones.store'), $this->interactionData($cliente, ['descripcion' => '']))
            ->assertSessionHasErrors('descripcion');
    }

    public function test_responsible_user_is_always_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $cliente = Cliente::factory()->create();

        $this->actingAs($user)->post(route('interacciones.store'), [
            ...$this->interactionData($cliente),
            'usuario_id' => $other->id,
        ]);

        $this->assertDatabaseHas('interacciones', ['usuario_id' => $user->id]);
        $this->assertDatabaseMissing('interacciones', ['usuario_id' => $other->id]);
    }

    public function test_interaction_belongs_to_selected_customer(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();
        $other = Cliente::factory()->create();

        $this->actingAs($user)->post(route('interacciones.store'), $this->interactionData($cliente));

        $this->assertCount(1, $cliente->interacciones);
        $this->assertCount(0, $other->interacciones);
    }

    public function test_history_is_ordered_newest_first(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();
        Interaccion::factory()->create([
            'cliente_id' => $cliente->id,
            'usuario_id' => $user->id,
            'descripcion' => 'Interacción más antigua',
            'fecha' => '2026-08-01 09:00:00',
        ]);
        Interaccion::factory()->create([
            'cliente_id' => $cliente->id,
            'usuario_id' => $user->id,
            'descripcion' => 'Interacción más reciente',
            'fecha' => '2026-08-02 09:00:00',
        ]);

        $this->actingAs($user)
            ->get(route('clientes.show', $cliente))
            ->assertSeeInOrder(['Interacción más reciente', 'Interacción más antigua']);
    }

    public function test_interaction_api_creates_interaction(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();

        $this->actingAs($user)
            ->postJson(route('api.interacciones.store'), [
                ...$this->interactionData($cliente, ['tipo' => Interaccion::TIPO_CORREO]),
                'usuario_id' => User::factory()->create()->id,
            ])
            ->assertCreated()
            ->assertJsonPath('data.cliente_id', $cliente->id)
            ->assertJsonPath('data.tipo', Interaccion::TIPO_CORREO)
            ->assertJsonPath('data.usuario.id', $user->id);
    }

    public function test_interaction_api_lists_customer_history_newest_first(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();
        Interaccion::factory()->create([
            'cliente_id' => $cliente->id,
            'usuario_id' => $user->id,
            'descripcion' => 'Anterior API',
            'fecha' => '2026-08-01 09:00:00',
        ]);
        $newest = Interaccion::factory()->create([
            'cliente_id' => $cliente->id,
            'usuario_id' => $user->id,
            'descripcion' => 'Reciente API',
            'fecha' => '2026-08-02 09:00:00',
        ]);

        $this->actingAs($user)
            ->getJson(route('api.clientes.interacciones.index', $cliente))
            ->assertOk()
            ->assertJsonPath('data.0.id', $newest->id)
            ->assertJsonCount(2, 'data');
    }

    public function test_guest_cannot_use_interaction_api(): void
    {
        $cliente = Cliente::factory()->create();

        $this->getJson(route('api.clientes.interacciones.index', $cliente))->assertUnauthorized();
        $this->postJson(route('api.interacciones.store'), $this->interactionData($cliente))->assertUnauthorized();
    }

    public function test_new_customer_has_prospect_stage_by_default(): void
    {
        $cliente = Cliente::create([
            'nombre' => 'Cliente nuevo',
            'correo' => 'nuevo@example.com',
            'telefono' => '5512345678',
            'estado' => Cliente::ESTADO_ACTIVO,
            'fecha_registro' => now()->toDateString(),
        ]);

        $this->assertSame(Cliente::ETAPA_PROSPECTO, $cliente->fresh()->etapa_crm);
    }

    public function test_authenticated_user_can_change_customer_stage(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();

        $this->actingAs($user)
            ->put(route('clientes.etapa.update', $cliente), ['etapa_crm' => Cliente::ETAPA_FRECUENTE])
            ->assertRedirect(route('clientes.show', $cliente))
            ->assertSessionHas('success');

        $this->assertSame(Cliente::ETAPA_FRECUENTE, $cliente->fresh()->etapa_crm);
    }

    public function test_invalid_customer_stage_is_rejected(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();

        $this->actingAs($user)
            ->put(route('clientes.etapa.update', $cliente), ['etapa_crm' => 'oportunidad'])
            ->assertSessionHasErrors('etapa_crm');

        $this->assertSame(Cliente::ETAPA_PROSPECTO, $cliente->fresh()->etapa_crm);
    }

    public function test_customer_list_displays_and_filters_by_stage(): void
    {
        $user = User::factory()->create();
        $frequent = Cliente::factory()->create(['etapa_crm' => Cliente::ETAPA_FRECUENTE]);
        $prospect = Cliente::factory()->create(['etapa_crm' => Cliente::ETAPA_PROSPECTO]);

        $this->actingAs($user)
            ->get(route('clientes.index', ['etapa_crm' => Cliente::ETAPA_FRECUENTE]))
            ->assertOk()
            ->assertSee('Frecuente')
            ->assertSee($frequent->nombre)
            ->assertDontSee($prospect->nombre)
            ->assertSee('value="frecuente" selected', false);
    }

    public function test_customer_filters_can_be_combined(): void
    {
        $user = User::factory()->create();
        $match = Cliente::factory()->create([
            'nombre' => 'Acme Principal',
            'estado' => Cliente::ESTADO_ACTIVO,
            'etapa_crm' => Cliente::ETAPA_PROSPECTO,
        ]);
        $wrongStage = Cliente::factory()->create([
            'nombre' => 'Acme Frecuente',
            'estado' => Cliente::ESTADO_ACTIVO,
            'etapa_crm' => Cliente::ETAPA_FRECUENTE,
        ]);
        $wrongSearch = Cliente::factory()->create([
            'nombre' => 'Delta Principal',
            'estado' => Cliente::ESTADO_ACTIVO,
            'etapa_crm' => Cliente::ETAPA_PROSPECTO,
        ]);

        $this->actingAs($user)
            ->get(route('clientes.index', [
                'buscar' => 'Acme',
                'estado' => Cliente::ESTADO_ACTIVO,
                'etapa_crm' => Cliente::ETAPA_PROSPECTO,
            ]))
            ->assertOk()
            ->assertSee($match->nombre)
            ->assertDontSee($wrongStage->nombre)
            ->assertDontSee($wrongSearch->nombre);
    }

    public function test_customer_stage_api_updates_stage(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();

        $this->actingAs($user)
            ->putJson(route('api.clientes.etapa.update', $cliente), ['etapa_crm' => Cliente::ETAPA_ACTIVO])
            ->assertOk()
            ->assertJsonPath('data.etapa_crm', Cliente::ETAPA_ACTIVO);
    }

    public function test_customer_api_returns_stage(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create(['etapa_crm' => Cliente::ETAPA_INACTIVO]);

        $this->actingAs($user)
            ->getJson(route('api.clientes.show', $cliente))
            ->assertOk()
            ->assertJsonPath('data.etapa_crm', Cliente::ETAPA_INACTIVO);
    }

    public function test_customer_and_user_have_interaction_relationships(): void
    {
        $user = User::factory()->create();
        $cliente = Cliente::factory()->create();
        $interaccion = Interaccion::factory()->create([
            'cliente_id' => $cliente->id,
            'usuario_id' => $user->id,
        ]);

        $this->assertTrue($cliente->interacciones->contains($interaccion));
        $this->assertTrue($user->interacciones->contains($interaccion));
        $this->assertTrue($interaccion->cliente->is($cliente));
        $this->assertTrue($interaccion->usuario->is($user));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function interactionData(Cliente $cliente, array $overrides = []): array
    {
        return array_merge([
            'cliente_id' => $cliente->id,
            'tipo' => Interaccion::TIPO_LLAMADA,
            'descripcion' => 'Seguimiento realizado con el cliente.',
            'fecha' => '2026-08-31 10:30:00',
        ], $overrides);
    }
}
