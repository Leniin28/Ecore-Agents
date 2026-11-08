<?php

namespace App\Console\Commands;

use Database\Seeders\StoreDemoSeeder;
use Illuminate\Console\Command;

class SeedStoreCommand extends Command
{
    protected $signature = 'ecore:seed-store';

    protected $description = 'Carga de datos demo aditiva e idempotente para la tienda comercial de planes y complementos ECore';

    public function handle(): int
    {
        $this->info('Iniciando carga demo comercial para ECore Agents...');

        $seeder = new StoreDemoSeeder();
        $seeder->run();

        $this->info('✓ Categorías, planes, complementos, IVA 16% y promociones verificados/creados correctamente.');

        return Command::SUCCESS;
    }
}
