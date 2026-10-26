<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/** Conserva el área de entrada sin estados globales de sesión ni datos duplicados. */
class ModuleNavigation
{
    public static function route(string $name, mixed $parameters = []): string
    {
        if (request()->routeIs('erp.*')) {
            foreach (['clientes.' => 'erp.clientes.', 'interacciones.' => 'erp.interacciones.',
                'scm.productos.' => 'erp.productos.', 'scm.inventario.' => 'erp.inventario.',
                'scm.movimientos.' => 'erp.movimientos.', 'admin.usuarios.' => 'erp.usuarios.'] as $from => $to) {
                if (str_starts_with($name, $from)) {
                    $candidate = $to.substr($name, strlen($from));
                    if (Route::has($candidate)) {
                        $name = $candidate;
                    }
                    break;
                }
            }
        }

        return route($name, $parameters);
    }
}
