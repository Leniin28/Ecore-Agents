<?php

namespace Tests\Concerns;

use App\Models\Cliente;
use App\Models\OrdenEmpresarial;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;

trait ErpFixtures
{
    private function erpUser(array $permissions = ['erp']): User
    {
        return User::factory()->create(['permissions' => $permissions]);
    }

    private function erpProduct(int $stock = 100, string $strategy = 'PULL', string $price = '0.35', int $minimum = 10): Producto
    {
        return Producto::create([
            'nombre' => 'Capacidad '.uniqid(), 'categoria' => 'IA',
            'proveedor_id' => Proveedor::create(['nombre' => 'Proveedor demo'])->id,
            'stock_actual' => $stock, 'stock_minimo' => $minimum,
            'costo_unitario' => '0.10', 'precio_venta_unitario' => $price, 'estrategia_logistica' => $strategy,
        ]);
    }

    private function erpOrder(User $user, array $lines): OrdenEmpresarial
    {
        $response = $this->actingAs($user)->postJson('/api/erp/ordenes', [
            'cliente_id' => Cliente::factory()->create()->id,
            'detalles' => $lines,
        ])->assertCreated();

        return OrdenEmpresarial::findOrFail($response->json('data.id'));
    }
}
