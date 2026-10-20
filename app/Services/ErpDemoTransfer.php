<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/** Transferencia local de la demo ERP: instantánea, no sincronización de bases. */
class ErpDemoTransfer
{
    public const COLUMNS = [
        'users' => ['id', 'name', 'email', 'email_verified_at', 'password', 'role', 'permissions', 'perfil_empresarial', 'created_at', 'updated_at'],
        'proveedores' => ['id', 'nombre', 'contacto', 'correo', 'telefono', 'created_at', 'updated_at'],
        'clientes' => ['id', 'user_id', 'nombre', 'correo', 'telefono', 'empresa', 'fecha_registro', 'estado', 'etapa_crm', 'created_at', 'updated_at'],
        'productos' => ['id', 'nombre', 'descripcion', 'categoria', 'stock_actual', 'stock_minimo', 'proveedor_id', 'costo_unitario', 'precio_venta_unitario', 'estrategia_logistica', 'created_at', 'updated_at'],
        'ordenes_empresariales' => ['id', 'cliente_id', 'usuario_id', 'fecha', 'estado', 'total', 'procesada_at', 'created_at', 'updated_at'],
        'orden_detalles' => ['id', 'orden_empresarial_id', 'producto_id', 'cantidad', 'precio_unitario', 'subtotal', 'created_at', 'updated_at'],
        'movimientos_inventario' => ['id', 'producto_id', 'usuario_id', 'tipo', 'cantidad', 'motivo', 'fecha', 'orden_detalle_id', 'created_at', 'updated_at'],
        'pedidos' => ['id', 'producto_id', 'usuario_id', 'cantidad', 'tipo', 'estado', 'origen', 'created_at', 'updated_at'],
        'procesos_internos' => ['id', 'orden_empresarial_id', 'usuario_id', 'tipo', 'descripcion', 'estado', 'fecha', 'created_at', 'updated_at'],
        'interacciones' => ['id', 'cliente_id', 'usuario_id', 'tipo', 'descripcion', 'fecha', 'created_at', 'updated_at'],
        'auditorias' => ['id', 'usuario_id', 'accion', 'entidad', 'entidad_id', 'descripcion', 'created_at', 'updated_at'],
    ];

    private const FOREIGN_KEYS = [
        'clientes' => ['user_id' => 'users'],
        'productos' => ['proveedor_id' => 'proveedores'],
        'ordenes_empresariales' => ['cliente_id' => 'clientes', 'usuario_id' => 'users'],
        'orden_detalles' => ['orden_empresarial_id' => 'ordenes_empresariales', 'producto_id' => 'productos'],
        'movimientos_inventario' => ['producto_id' => 'productos', 'usuario_id' => 'users', 'orden_detalle_id' => 'orden_detalles'],
        'pedidos' => ['producto_id' => 'productos', 'usuario_id' => 'users'],
        'procesos_internos' => ['orden_empresarial_id' => 'ordenes_empresariales', 'usuario_id' => 'users'],
        'interacciones' => ['cliente_id' => 'clientes', 'usuario_id' => 'users'],
        'auditorias' => ['usuario_id' => 'users'],
    ];

    private const ENTITIES = [
        'usuario' => 'users', 'proveedor' => 'proveedores', 'cliente' => 'clientes',
        'producto' => 'productos', 'orden_empresarial' => 'ordenes_empresariales',
        'movimiento_inventario' => 'movimientos_inventario', 'pedido' => 'pedidos',
        'proceso_interno' => 'procesos_internos', 'interaccion' => 'interacciones',
    ];

    private const EMAILS = ['ventas.erp.demo@ecore.local', 'logistica.erp.demo@ecore.local'];

    private const CLIENT_EMAILS = ['aurora.erp.demo@ecore.local', 'nexo.erp.demo@ecore.local'];

    private const PRODUCTS = ['DEMO ERP · Razonamiento PUSH', 'DEMO ERP · General PULL', 'DEMO ERP · Multimodal', 'DEMO ERP · Ligero', 'DEMO ERP · Respaldo'];

    private const SUPPLIER = 'DEMO ERP · Proveedor tecnológico';

    public function export(): array
    {
        $this->guard();

        // Una sola instantánea de lectura, incluso si la aplicación está abierta.
        return DB::transaction(function () {
            $tables = [];
            $tables['users'] = $this->rows('users', 'email', self::EMAILS);
            $tables['proveedores'] = $this->rows('proveedores', 'nombre', [self::SUPPLIER]);
            $tables['clientes'] = $this->rows('clientes', 'correo', self::CLIENT_EMAILS);
            $tables['productos'] = $this->rows('productos', 'nombre', self::PRODUCTS);
            if (count($tables['users']) !== 2 || count($tables['proveedores']) !== 1
                || count($tables['clientes']) !== 2 || count($tables['productos']) !== 5) {
                throw new RuntimeException('No se encontró completa la demo ERP local. No se exportaron otros datos.');
            }
            $productIds = array_column($tables['productos'], 'id');
            $clientIds = array_column($tables['clientes'], 'id');
            // Incluye todas las órdenes posteriores, pero no mezcla clientes/productos ajenos.
            $orderIds = DB::table('ordenes_empresariales')->whereIn('cliente_id', $clientIds)->pluck('id')
                ->merge(DB::table('orden_detalles')->whereIn('producto_id', $productIds)->pluck('orden_empresarial_id'))->unique()->all();
            $tables['ordenes_empresariales'] = $this->rows('ordenes_empresariales', 'id', $orderIds);
            $tables['orden_detalles'] = $this->rows('orden_detalles', 'orden_empresarial_id', $orderIds);
            $tables['movimientos_inventario'] = $this->rows('movimientos_inventario', 'producto_id', $productIds);
            $tables['pedidos'] = $this->rows('pedidos', 'producto_id', $productIds);
            $tables['procesos_internos'] = $this->rows('procesos_internos', 'orden_empresarial_id', $orderIds);
            $tables['interacciones'] = $this->rows('interacciones', 'cliente_id', $clientIds);
            $tables['auditorias'] = DB::table('auditorias')->where(function ($query) use ($tables) {
                foreach (self::ENTITIES as $entity => $table) {
                    $query->orWhere(fn ($q) => $q->where('entidad', $entity)->whereIn('entidad_id', array_column($tables[$table] ?? [], 'id')));
                }
            })->orderBy('id')->get(self::COLUMNS['auditorias'])->map(fn ($r) => (array) $r)->all();

            $actorIds = [];
            foreach ($tables as $table => $rows) {
                foreach ($rows as $row) {
                    foreach (self::FOREIGN_KEYS[$table] ?? [] as $column => $parent) {
                        if ($parent === 'users' && $row[$column] !== null) {
                            $actorIds[] = $row[$column];
                        }
                    }
                }
            }
            $external = DB::table('users')->whereIn('id', $actorIds)->whereNotIn('id', array_column($tables['users'], 'id'))
                ->orderBy('id')->get(['id', 'email'])->map(fn ($r) => (array) $r)->all();
            $payload = ['format' => 'ecore-erp-demo-v1', 'tables' => $tables, 'external_actors' => $external,
                'source_erp_level' => DB::table('estado_erp')->value('nivel')];
            $this->validate($payload);

            return ['sha256' => $this->digest($payload), 'payload' => $payload];
        });
    }

    /** Vista previa por defecto; nunca actualiza ni reutiliza entidades demo existentes. */
    public function import(array $bundle, bool $apply = false, array $actorAliases = []): array
    {
        $this->guard();
        $payload = $bundle['payload'] ?? [];
        if (! is_array($payload) || ! is_string($bundle['sha256'] ?? null)
            || ! hash_equals($this->digest($payload), $bundle['sha256'])) {
            throw new RuntimeException('La huella del paquete no coincide. No se modificó la base.');
        }
        $this->validate($payload);
        $sha = $bundle['sha256'];
        $plan = $this->preflight($payload, $sha, $actorAliases);
        if (! $apply || $plan['already_imported']) {
            return $plan;
        }
        $backup = $this->backup();
        $result = DB::transaction(function () use ($payload, $sha, $actorAliases) {
            // Repite las comprobaciones dentro de la transacción antes de insertar.
            $plan = $this->preflight($payload, $sha, $actorAliases);
            if ($plan['already_imported']) {
                return $plan;
            }
            $map = ['users' => $plan['actors']];
            foreach ($payload['tables'] as $table => $rows) {
                foreach ($rows as $row) {
                    $sourceId = $row['id'];
                    unset($row['id']);
                    foreach (self::FOREIGN_KEYS[$table] ?? [] as $column => $parent) {
                        if ($row[$column] !== null) {
                            $row[$column] = $map[$parent][$row[$column]];
                        }
                    }
                    if ($table === 'auditorias') {
                        $parent = self::ENTITIES[$row['entidad']];
                        $row['entidad_id'] = $map[$parent][$row['entidad_id']];
                        // Conserva el texto histórico: puede contener el folio de origen.
                    }
                    // Query Builder evita observers y no vuelve a procesar ventas/PUSH.
                    $map[$table][$sourceId] = DB::table($table)->insertGetId($row);
                }
            }
            $plan['order_ids'] = $map['ordenes_empresariales'] ?? [];
            DB::table('auditorias')->insert([
                'usuario_id' => null, 'accion' => 'importar_demo_erp', 'entidad' => 'transferencia_demo',
                'entidad_id' => null, 'descripcion' => json_encode(['sha256' => $sha, 'order_ids' => $plan['order_ids']], JSON_THROW_ON_ERROR),
                'created_at' => now(), 'updated_at' => now(),
            ]);

            return $plan;
        });
        $result['backup'] = $backup;

        return $result;
    }

    private function preflight(array $payload, string $sha, array $aliases): array
    {
        $receipt = DB::table('auditorias')->where('accion', 'importar_demo_erp')->where('entidad', 'transferencia_demo')
            ->get()->first(function ($r) use ($sha) {
                return (json_decode($r->descripcion, true)['sha256'] ?? null) === $sha;
            });
        if ($receipt) {
            return ['already_imported' => true, 'counts' => [], 'actors' => [],
                'order_ids' => json_decode($receipt->descripcion, true)['order_ids'] ?? []];
        }
        $actors = [];
        foreach ($payload['external_actors'] as $actor) {
            $email = $aliases[$actor['email']] ?? $actor['email'];
            $id = DB::table('users')->whereRaw('lower(email) = ?', [strtolower($email)])->value('id');
            if (! $id) {
                throw new RuntimeException("Falta el actor histórico {$actor['email']}. Usa --actor={$actor['email']}=correo-existente-en-destino. No se crean administradores ni se copian sus contraseñas.");
            }
            $actors[$actor['id']] = $id;
        }
        foreach (['users' => 'email', 'clientes' => 'correo', 'proveedores' => 'nombre', 'productos' => 'nombre'] as $table => $key) {
            foreach ($payload['tables'][$table] as $row) {
                if (DB::table($table)->whereRaw('lower('.$key.') = ?', [mb_strtolower($row[$key])])->exists()) {
                    throw new RuntimeException("Conflicto en {$table}: {$row[$key]}. Se detuvo todo sin sobrescribir ni duplicar la demo existente.");
                }
            }
        }

        return ['already_imported' => false, 'counts' => array_map('count', $payload['tables']), 'actors' => $actors,
            'source_erp_level' => $payload['source_erp_level'], 'order_ids' => []];
    }

    private function validate(array $payload): void
    {
        if (($payload['format'] ?? '') !== 'ecore-erp-demo-v1'
            || array_keys($payload['tables'] ?? []) !== array_keys(self::COLUMNS)
            || ! is_array($payload['external_actors'] ?? null)) {
            throw new RuntimeException('Formato de transferencia no admitido.');
        }
        $ids = [];
        foreach ($payload['tables'] as $table => $rows) {
            if (! is_array($rows) || ! array_is_list($rows)) {
                throw new RuntimeException('Lista de registros inválida.');
            }
            foreach ($rows as $row) {
                if (! is_array($row) || array_keys($row) !== self::COLUMNS[$table]
                    || ! is_int($row['id']) || $row['id'] < 1 || isset($ids[$table][$row['id']])) {
                    throw new RuntimeException("Columnas o ID inválidos en {$table}.");
                }
                foreach ($row as $value) {
                    if (! is_scalar($value) && $value !== null) {
                        throw new RuntimeException('Un registro contiene valores no escalares.');
                    }
                }
                $ids[$table][$row['id']] = true;
            }
        }
        foreach ($payload['external_actors'] as $actor) {
            if (! is_array($actor) || array_keys($actor) !== ['id', 'email'] || ! is_int($actor['id']) || $actor['id'] < 1
                || ! filter_var($actor['email'], FILTER_VALIDATE_EMAIL) || isset($ids['users'][$actor['id']])) {
                throw new RuntimeException('Referencia de actor inválida.');
            }
            $ids['users'][$actor['id']] = true;
        }
        foreach (['users' => 2, 'proveedores' => 1, 'clientes' => 2, 'productos' => 5] as $table => $count) {
            if (count($payload['tables'][$table]) !== $count) {
                throw new RuntimeException('La demo está incompleta o contiene identificadores duplicados.');
            }
        }
        foreach ($payload['tables'] as $table => $rows) {
            foreach ($rows as $row) {
                foreach (self::FOREIGN_KEYS[$table] ?? [] as $column => $parent) {
                    if ($row[$column] !== null && (! is_int($row[$column]) || ! isset($ids[$parent][$row[$column]]))) {
                        throw new RuntimeException("La demo se mezcla con datos ajenos o falta una relación: {$table}.{$column}. No se exportan datos ajenos automáticamente.");
                    }
                }
                if ($table === 'auditorias' && ! isset($ids[self::ENTITIES[$row['entidad']] ?? ''][$row['entidad_id']])) {
                    throw new RuntimeException('Referencia de auditoría no incluida en el paquete.');
                }
            }
        }
        foreach ($payload['tables']['users'] as $user) {
            $permissions = json_decode($user['permissions'] ?? '[]', true);
            if (! in_array($user['email'], self::EMAILS, true) || $user['role'] !== 'usuario'
                || ! is_array($permissions) || ! array_is_list($permissions)
                || array_filter($permissions, fn ($p) => ! is_string($p) || ! in_array($p, ['crm', 'scm', 'erp'], true))
                || ! password_get_info($user['password'])['algo']) {
                throw new RuntimeException('Solo se admiten cuentas demo de empleado con contraseña ya cifrada.');
            }
        }
        if (count(array_unique(array_column($payload['tables']['users'], 'email'))) !== count(self::EMAILS)) {
            throw new RuntimeException('La demo contiene cuentas duplicadas.');
        }
        foreach (['clientes' => ['correo', self::CLIENT_EMAILS], 'productos' => ['nombre', self::PRODUCTS],
            'proveedores' => ['nombre', [self::SUPPLIER]]] as $table => [$key, $allowed]) {
            foreach ($payload['tables'][$table] as $row) {
                if (! in_array($row[$key], $allowed, true)) {
                    throw new RuntimeException('El paquete contiene entidades que no pertenecen a la demo ERP.');
                }
            }
            if (count(array_unique(array_column($payload['tables'][$table], $key))) !== count($allowed)) {
                throw new RuntimeException('La demo contiene identificadores duplicados.');
            }
        }
    }

    private function rows(string $table, string $column, array $ids): array
    {
        return DB::table($table)->whereIn($column, $ids)->orderBy('id')->get(self::COLUMNS[$table])->map(fn ($r) => (array) $r)->all();
    }

    public function digest(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));
    }

    private function backup(): ?string
    {
        $db = DB::connection();
        if ($db->getDatabaseName() === ':memory:') {
            return null;
        }
        $directory = storage_path('app/private/erp-demo-backups');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true)) {
            throw new RuntimeException('No se pudo crear la carpeta de respaldos.');
        }
        $path = $directory.'/before-import-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.sqlite';
        $db->statement('VACUUM INTO '.$db->getPdo()->quote($path));
        $copy = new \PDO('sqlite:'.$path);
        if ($copy->query('PRAGMA integrity_check')->fetchColumn() !== 'ok') {
            throw new RuntimeException('La copia de seguridad no pasó la validación. No se inició la importación.');
        }

        return $path;
    }

    private function guard(): void
    {
        if (! app()->environment(['local', 'testing']) || DB::connection()->getDriverName() !== 'sqlite') {
            throw new RuntimeException('Esta transferencia solo se permite en SQLite local/testing.');
        }
    }
}
