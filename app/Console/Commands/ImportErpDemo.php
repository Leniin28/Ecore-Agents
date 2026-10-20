<?php

namespace App\Console\Commands;

use App\Services\ErpDemoTransfer;
use Illuminate\Console\Command;
use Throwable;

class ImportErpDemo extends Command
{
    protected $signature = 'erp:demo-import {file : Paquete JSON privado de erp:demo-export} {--apply : Aplicar la importación, con respaldo previo} {--actor=* : Equivalencia explícita correo-origen=correo-destino para actores históricos}';

    protected $description = 'Revisa o importa una demo ERP sin borrar ni sobrescribir registros existentes';

    public function handle(ErpDemoTransfer $transfer): int
    {
        try {
            $path = $this->argument('file');
            if (! is_file($path) || filesize($path) > 5 * 1024 * 1024) {
                throw new \RuntimeException('El paquete debe ser un archivo JSON de hasta 5 MiB.');
            }
            $aliases = [];
            foreach ($this->option('actor') as $alias) {
                $pair = explode('=', $alias, 2);
                if (count($pair) !== 2 || ! filter_var($pair[0], FILTER_VALIDATE_EMAIL) || ! filter_var($pair[1], FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('Usa --actor=correo-origen=correo-destino.');
                }
                $aliases[$pair[0]] = $pair[1];
            }
            $bundle = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (! is_array($bundle)) {
                throw new \RuntimeException('El paquete JSON no es válido.');
            }
            $plan = $transfer->import($bundle, (bool) $this->option('apply'), $aliases);
            if ($plan['already_imported']) {
                $this->info('Este paquete ya fue importado. No se modificó ni duplicó ningún registro.');
            } else {
                $this->table(['Tabla', 'Registros nuevos'], collect($plan['counts'])->map(fn ($n, $t) => [$t, $n])->values()->all());
                $this->info($this->option('apply') ? 'Importación terminada sin sobrescribir registros existentes.' : 'Solo revisión: no se modificó la base. Agrega --apply para importar.');
                $this->line('Se conserva el nivel ERP/configuración del destino. Nivel de origen: '.($plan['source_erp_level'] ?? 'sin declarar'));
            }
            foreach ($plan['order_ids'] as $source => $target) {
                $this->line(sprintf('Folio de origen ORD-%05d → destino ORD-%05d', $source, $target));
            }
            if (isset($plan['backup'])) {
                $this->line('Respaldo SQLite previo: '.$plan['backup']);
            }

            return self::SUCCESS;
        } catch (Throwable $error) {
            $this->error($error->getMessage());

            return self::FAILURE;
        }
    }
}
