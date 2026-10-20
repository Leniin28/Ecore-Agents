<?php

namespace App\Console\Commands;

use App\Services\ErpDemoTransfer;
use Illuminate\Console\Command;
use Throwable;

class ExportErpDemo extends Command
{
    protected $signature = 'erp:demo-export {file : Archivo JSON nuevo, fuera del repositorio público}';

    protected $description = 'Exporta el estado actual de la demo ERP sin modificar la base local';

    public function handle(ErpDemoTransfer $transfer): int
    {
        try {
            $bundle = $transfer->export();
            $json = json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR).PHP_EOL;
            $file = fopen($this->argument('file'), 'x');
            if (! $file) {
                throw new \RuntimeException('No se pudo crear el archivo; no se sobrescriben archivos existentes.');
            }
            try {
                if (fwrite($file, $json) !== strlen($json)) {
                    throw new \RuntimeException('No se pudo completar el archivo. No transfieras una exportación incompleta.');
                }
            } finally {
                fclose($file);
            }
            $this->info('Demo exportada. SHA-256 del contenido: '.$bundle['sha256']);
            $this->table(['Tabla', 'Registros'], collect($bundle['payload']['tables'])->map(fn ($r, $t) => [$t, count($r)])->values()->all());
            $this->warn('Archivo privado: contiene hashes de contraseñas de las dos cuentas demo. No lo subas a Git ni lo publiques.');

            return self::SUCCESS;
        } catch (Throwable $error) {
            $this->error($error->getMessage());

            return self::FAILURE;
        }
    }
}
