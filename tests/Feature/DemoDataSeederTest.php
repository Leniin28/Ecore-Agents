<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_is_fictitious_complete_and_idempotent(): void
    {
        $this->seed(DemoDataSeeder::class);

        $firstCounts = $this->counts();
        $this->assertSame(['users' => 1, 'suppliers' => 1, 'products' => 5, 'movements' => 12, 'orders' => 4, 'audits' => 0], $firstCounts);

        $employee = User::query()->where('email', 'empleado.scm@ecore.local')->firstOrFail();
        $this->assertSame(User::ROLE_EMPLEADO, $employee->role);
        $this->assertSame([User::MODULE_SCM], $employee->permissions);
        $this->assertTrue(Hash::check('ECoreDemo2026!', $employee->password));
        $this->assertSame(3, Producto::query()->where('estrategia_logistica', Producto::ESTRATEGIA_PUSH)->count());
        $this->assertSame(2, Producto::query()->where('estrategia_logistica', Producto::ESTRATEGIA_PULL)->count());
        $this->assertDatabaseHas('pedidos', ['estado' => Pedido::ESTADO_PENDIENTE, 'origen' => Pedido::ORIGEN_MANUAL]);
        $this->assertDatabaseHas('pedidos', ['estado' => Pedido::ESTADO_SURTIDO, 'origen' => Pedido::ORIGEN_AUTOMATICO_PUSH]);

        $this->seed(DemoDataSeeder::class);

        $this->assertSame($firstCounts, $this->counts());
        $this->assertSame(5, Producto::query()->distinct()->count('nombre'));
    }

    /** @return array<string, int> */
    private function counts(): array
    {
        return [
            'users' => User::query()->count(),
            'suppliers' => Proveedor::query()->count(),
            'products' => Producto::query()->count(),
            'movements' => MovimientoInventario::query()->count(),
            'orders' => Pedido::query()->count(),
            'audits' => Auditoria::query()->count(),
        ];
    }
}
