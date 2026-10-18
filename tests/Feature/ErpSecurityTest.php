<?php

namespace Tests\Feature;

use App\Models\Cliente;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ErpFixtures;
use Tests\TestCase;

class ErpSecurityTest extends TestCase
{
    use RefreshDatabase, ErpFixtures;

    public function test_session_api_writes_require_csrf_outside_test_environment(): void
    {
        $product = $this->erpProduct();
        $user = $this->erpUser();
        $payload = ['cliente_id' => Cliente::factory()->create()->id, 'detalles' => [['producto_id' => $product->id, 'cantidad' => 1]]];
        $this->app['env'] = 'local';
        $this->actingAs($user)->withSession(['_token' => 'erp-csrf-test-token'])
            ->postJson('/api/erp/ordenes', $payload)->assertStatus(419);
        $this->withSession(['_token' => 'erp-csrf-test-token'])
            ->postJson('/api/erp/ordenes', [...$payload, '_token' => 'erp-csrf-test-token'])->assertCreated();
        $this->assertDatabaseCount('ordenes_empresariales', 1);
    }

    public function test_demo_seeder_does_not_write_in_production(): void
    {
        $this->app['env'] = 'production';
        app(DemoDataSeeder::class)->run();
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('productos', 0);
        $this->assertDatabaseCount('ordenes_empresariales', 0);
    }

    public function test_total_limit_rejects_order_without_partial_details(): void
    {
        $product = $this->erpProduct(price: '9999999999.99');
        $this->actingAs($this->erpUser())->postJson('/api/erp/ordenes', [
            'cliente_id' => Cliente::factory()->create()->id,
            'detalles' => [['producto_id' => $product->id, 'cantidad' => 1000000]],
        ])->assertUnprocessable();
        $this->assertDatabaseCount('ordenes_empresariales', 0);
        $this->assertDatabaseCount('orden_detalles', 0);
    }
}
