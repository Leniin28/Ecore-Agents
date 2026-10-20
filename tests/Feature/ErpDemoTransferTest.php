<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\EstadoErp;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Services\ErpDemoTransfer;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class ErpDemoTransferTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_is_read_only_scoped_and_has_no_admin_credentials_or_session_tokens(): void
    {
        $this->fixture();
        $before = $this->snapshot();
        $bundle = app(ErpDemoTransfer::class)->export();
        $this->assertSame($before, $this->snapshot());
        $this->assertCount(2, $bundle['payload']['tables']['users']);
        $json = json_encode($bundle);
        $this->assertStringNotContainsString('private-person@example.test', $json);
        $this->assertStringNotContainsString('secret-session-token', $json);
        $this->assertStringNotContainsString('Admin password must stay here', $json);
        $this->assertStringNotContainsString(User::where('email', 'admin@ecore.local')->value('password'), $json);
        $this->assertSame(['id', 'email'], array_keys($bundle['payload']['external_actors'][0]));
        $this->assertSame('admin@ecore.local', $bundle['payload']['external_actors'][0]['email']);
        $this->assertSame(2, count($bundle['payload']['tables']['ordenes_empresariales']));
        $this->assertSame(1, count($bundle['payload']['tables']['pedidos']));
        $this->assertSame('cancelada', $bundle['payload']['tables']['ordenes_empresariales'][1]['estado']);
        $this->assertSame(700, $bundle['payload']['tables']['productos'][0]['stock_actual']);
    }

    public function test_preview_and_import_preserve_destination_rows_remap_all_history_and_never_replay_stock(): void
    {
        $bundle = $this->destination();
        $before = $this->snapshot();
        $transfer = app(ErpDemoTransfer::class);
        $aliases = ['admin@ecore.local' => 'owner@destination.test'];
        $preview = $transfer->import($bundle, false, $aliases);
        $this->assertFalse($preview['already_imported']);
        $this->assertSame($before, $this->snapshot());

        $result = $transfer->import($bundle, true, $aliases);
        $this->assertFalse($result['already_imported']);
        $this->assertExistingPreserved($before);
        $this->assertDatabaseHas('productos', ['nombre' => 'DEMO ERP · Razonamiento PUSH', 'stock_actual' => 700]);
        $this->assertDatabaseHas('productos', ['nombre' => 'DEMO ERP · General PULL', 'stock_actual' => 1200]);
        $this->assertSame('optimizado', EstadoErp::actual()->nivel);
        $this->assertDatabaseCount('pedidos', 1); // No segundo pedido PUSH al importar.
        $this->assertDatabaseCount('movimientos_inventario', 1); // No segunda venta.
        $orderIds = $result['order_ids'];
        foreach ($orderIds as $source => $target) {
            $this->assertNotEquals($source, $target);
        }
        $sourceOrder = $bundle['payload']['tables']['ordenes_empresariales'][0];
        $targetId = $orderIds[$sourceOrder['id']];
        $this->assertDatabaseHas('ordenes_empresariales', ['id' => $targetId, 'estado' => 'procesada', 'total' => 175]);
        $line = DB::table('orden_detalles')->where('orden_empresarial_id', $targetId)->first();
        $movement = DB::table('movimientos_inventario')->first();
        $this->assertSame($line->id, $movement->orden_detalle_id);
        $this->assertSame($line->producto_id, $movement->producto_id);
        $this->assertSame('2026-10-18 10:00:00', $movement->fecha);
        $owner = User::where('email', 'owner@destination.test')->firstOrFail();
        $this->assertDatabaseHas('procesos_internos', ['tipo' => 'orden_cancelada', 'usuario_id' => $owner->id]);
        $this->assertDatabaseHas('auditorias', ['entidad' => 'orden_empresarial', 'entidad_id' => $targetId, 'accion' => 'procesar_orden_erp']);
        $sales = User::where('email', 'ventas.erp.demo@ecore.local')->firstOrFail();
        $this->assertTrue(Hash::check('ECoreDemo2026!', $sales->password));
        $this->actingAs($sales)->get('/erp')->assertOk();
        foreach ($orderIds as $target) {
            $this->get('/erp/ordenes/'.$target)->assertOk();
        }
        $after = $this->snapshot();
        $this->assertTrue($transfer->import($bundle, true, $aliases)['already_imported']);
        $this->assertSame($after, $this->snapshot());
    }

    public function test_existing_demo_identity_aborts_everything_without_updates(): void
    {
        $bundle = $this->destination();
        User::factory()->create(['email' => 'ventas.erp.demo@ecore.local', 'permissions' => ['scm']]);
        $before = $this->snapshot();
        try {
            app(ErpDemoTransfer::class)->import($bundle, true, ['admin@ecore.local' => 'owner@destination.test']);
            $this->fail('La colisión debe detener toda la importación.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Conflicto en users', $e->getMessage());
        }
        $this->assertSame($before, $this->snapshot());
    }

    public function test_product_collision_is_not_silently_reused_or_overwritten(): void
    {
        $bundle = $this->destination();
        DB::table('productos')->where('nombre', 'Producto destino')->update(['nombre' => 'DEMO ERP · Razonamiento PUSH']);
        $before = $this->snapshot();
        try {
            app(ErpDemoTransfer::class)->import($bundle, true, ['admin@ecore.local' => 'owner@destination.test']);
            $this->fail('No debe fusionar inventarios.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Conflicto en productos', $e->getMessage());
        }
        $this->assertSame($before, $this->snapshot());
    }

    public function test_missing_historical_actor_requires_explicit_existing_identity(): void
    {
        $bundle = $this->destination();
        $before = $this->snapshot();
        try {
            app(ErpDemoTransfer::class)->import($bundle, true);
            $this->fail('Un actor faltante no se debe inventar ni crear como administrador.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Falta el actor histórico admin@ecore.local', $e->getMessage());
        }
        $this->assertSame($before, $this->snapshot());
    }

    public function test_checksum_and_foreign_key_tampering_are_rejected(): void
    {
        $bundle = $this->destination();
        $before = $this->snapshot();
        $bundle['payload']['tables']['productos'][0]['stock_actual'] = 99999;
        try {
            app(ErpDemoTransfer::class)->import($bundle, true);
            $this->fail('Debe comprobar la huella.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('huella', $e->getMessage());
        }
        $bundle['payload']['tables']['orden_detalles'][0]['producto_id'] = 99999;
        $bundle['sha256'] = app(ErpDemoTransfer::class)->digest($bundle['payload']);
        try {
            app(ErpDemoTransfer::class)->import($bundle, true);
            $this->fail('Debe comprobar las relaciones antes de escribir.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('falta una relación', $e->getMessage());
        }
        $this->assertSame($before, $this->snapshot());
    }

    public function test_late_database_error_rolls_back_all_inserted_demo_entities(): void
    {
        $bundle = $this->destination();
        DB::unprepared("CREATE TRIGGER reject_import_audit BEFORE INSERT ON auditorias WHEN NEW.accion = 'importar_demo_erp' BEGIN SELECT RAISE(ABORT, 'test failure'); END;");
        $before = $this->snapshot();
        try {
            app(ErpDemoTransfer::class)->import($bundle, true, ['admin@ecore.local' => 'owner@destination.test']);
            $this->fail('Debe revertir todas las inserciones.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('test failure', $e->getMessage());
        }
        $this->assertSame($before, $this->snapshot());
    }

    public function test_even_a_rehashed_package_cannot_import_admin_accounts_or_arbitrary_columns(): void
    {
        $bundle = $this->destination();
        $before = $this->snapshot();
        $transfer = app(ErpDemoTransfer::class);
        $changed = $bundle;
        $changed['payload']['tables']['users'][0]['role'] = 'admin';
        $changed['sha256'] = $transfer->digest($changed['payload']);
        try {
            $transfer->import($changed, true);
            $this->fail('No se deben crear administradores desde un paquete.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('cuentas demo de empleado', $e->getMessage());
        }
        $changed = $bundle;
        $changed['payload']['tables']['users'][0]['remember_token'] = 'must-not-import';
        $changed['sha256'] = $transfer->digest($changed['payload']);
        try {
            $transfer->import($changed, true);
            $this->fail('No se deben aceptar columnas fuera del formato explícito.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Columnas o ID inválidos', $e->getMessage());
        }
        $this->assertSame($before, $this->snapshot());
    }

    public function test_export_rejects_orders_mixing_demo_and_unrelated_products(): void
    {
        $this->fixture();
        $supplier = Proveedor::create(['nombre' => 'Ajeno']);
        $product = Producto::create(['nombre' => 'Ajeno', 'categoria' => 'IA', 'proveedor_id' => $supplier->id]);
        DB::table('orden_detalles')->where('id', DB::table('orden_detalles')->value('id'))->update(['producto_id' => $product->id]);
        $before = $this->snapshot();
        try {
            app(ErpDemoTransfer::class)->export();
            $this->fail('No debe extraer datos ajenos por dependencia.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('datos ajenos', $e->getMessage());
        }
        $this->assertSame($before, $this->snapshot());
    }

    public function test_commands_handle_files_preview_apply_repeat_and_corruption(): void
    {
        $this->fixture();
        $path = sys_get_temp_dir().'/erp-demo-test-'.bin2hex(random_bytes(8)).'.json';
        try {
            $this->artisan('erp:demo-export', ['file' => $path])->assertSuccessful();
            $this->assertFileExists($path);
            $bundle = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $this->clearSource();
            User::factory()->create(['email' => 'admin@ecore.local']);
            $before = $this->snapshot();
            $this->artisan('erp:demo-import', ['file' => $path])->assertSuccessful();
            $this->assertSame($before, $this->snapshot());
            $this->artisan('erp:demo-import', ['file' => $path, '--apply' => true])->assertSuccessful();
            $after = $this->snapshot();
            $this->artisan('erp:demo-import', ['file' => $path, '--apply' => true])->assertSuccessful();
            $this->assertSame($after, $this->snapshot());
            $this->assertSame($bundle['sha256'], json_decode(file_get_contents($path), true)['sha256']);
            $this->artisan('erp:demo-import', ['file' => $path.'.missing'])->assertFailed();
        } finally {
            @unlink($path);
        }
    }

    public function test_transfer_refuses_production_even_with_valid_data(): void
    {
        $this->fixture();
        $this->app->instance('env', 'production');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SQLite local/testing');
        app(ErpDemoTransfer::class)->export();
    }

    private function fixture(): void
    {
        $sales = User::factory()->create(['name' => 'DEMO ERP · Ventas', 'email' => 'ventas.erp.demo@ecore.local',
            'password' => 'ECoreDemo2026!', 'role' => 'usuario', 'permissions' => ['crm', 'erp'], 'perfil_empresarial' => 'ventas', 'remember_token' => 'secret-session-token']);
        $logistics = User::factory()->create(['email' => 'logistica.erp.demo@ecore.local', 'role' => 'usuario',
            'permissions' => ['scm', 'erp'], 'perfil_empresarial' => 'logistica']);
        $admin = User::factory()->create(['email' => 'admin@ecore.local', 'role' => 'admin', 'password' => 'Admin password must stay here']);
        Cliente::factory()->create(['correo' => 'private-person@example.test']);
        $clients = [];
        foreach (['aurora', 'nexo'] as $label) {
            $clients[] = Cliente::factory()->create(['correo' => $label.'.erp.demo@ecore.local', 'nombre' => 'DEMO ERP · '.$label]);
        }
        $supplier = Proveedor::create(['nombre' => 'DEMO ERP · Proveedor tecnológico']);
        $products = [];
        foreach (['Razonamiento PUSH', 'General PULL', 'Multimodal', 'Ligero', 'Respaldo'] as $i => $label) {
            $products[] = Producto::create(['nombre' => 'DEMO ERP · '.$label, 'categoria' => 'Demo ERP', 'proveedor_id' => $supplier->id,
                'stock_actual' => 1200, 'stock_minimo' => 1000, 'precio_venta_unitario' => '0.35',
                'estrategia_logistica' => $i === 0 ? 'PUSH' : 'PULL']);
        }
        $lines = [['producto_id' => $products[0]->id, 'cantidad' => 500]];
        $processed = $this->actingAs($sales)->postJson('/api/erp/ordenes', ['cliente_id' => $clients[0]->id, 'detalles' => $lines])->assertCreated()->json('data.id');
        $this->actingAs($logistics)->postJson('/api/erp/ordenes/'.$processed.'/procesar')->assertOk();
        DB::table('movimientos_inventario')->update(['fecha' => '2026-10-18 10:00:00']);
        $cancelled = $this->actingAs($sales)->postJson('/api/erp/ordenes', ['cliente_id' => $clients[1]->id, 'detalles' => $lines])->assertCreated()->json('data.id');
        $this->actingAs($admin)->postJson('/api/erp/ordenes/'.$cancelled.'/cancelar')->assertOk();
        DB::table('interacciones')->insert(['cliente_id' => $clients[0]->id, 'usuario_id' => $sales->id,
            'tipo' => 'llamada', 'descripcion' => 'Seguimiento demo', 'fecha' => '2026-10-18 11:00:00']);
        EstadoErp::actual()->update(['nivel' => 'automatizado']);
    }

    private function destination(): array
    {
        $this->fixture();
        $bundle = app(ErpDemoTransfer::class)->export();
        $this->clearSource();
        $owner = User::factory()->create(['email' => 'owner@destination.test', 'role' => 'admin']);
        $supplier = Proveedor::create(['nombre' => 'Proveedor destino']);
        Producto::create(['nombre' => 'Producto destino', 'categoria' => 'Real', 'proveedor_id' => $supplier->id, 'stock_actual' => 777]);
        $client = Cliente::factory()->create();
        DB::table('ordenes_empresariales')->insert(['cliente_id' => $client->id, 'usuario_id' => $owner->id,
            'estado' => 'pendiente', 'fecha' => '2026-10-19 12:00:00', 'total' => 0]);
        EstadoErp::actual()->update(['nivel' => 'optimizado']);

        return $bundle;
    }

    private function clearSource(): void
    {
        // Solo :memory: de PHPUnit; nunca se usa este helper sobre una base local.
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (array_reverse(array_keys(ErpDemoTransfer::COLUMNS)) as $table) {
            DB::table($table)->delete();
        }
    }

    private function snapshot(): array
    {
        $data = [];
        foreach ([...array_keys(ErpDemoTransfer::COLUMNS), 'estado_erp', 'configuracion_scm'] as $table) {
            $data[$table] = DB::table($table)->orderBy('id')->get()->mapWithKeys(fn ($r) => [$r->id => (array) $r])->all();
        }

        return $data;
    }

    private function assertExistingPreserved(array $before): void
    {
        $after = $this->snapshot();
        foreach ($before as $table => $rows) {
            foreach ($rows as $id => $row) {
                $this->assertSame($row, $after[$table][$id]);
            }
        }
    }
}
