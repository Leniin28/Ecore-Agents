<?php

namespace App\Providers;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as BladeView;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['clientes.*', 'scm.productos.*', 'scm.inventario.*', 'scm.movimientos.*', 'admin.usuarios.*'], function (BladeView $view): void {
            $view->with('erpContext', request()->routeIs('erp.*'))
                ->with('moduleRoute', \App\Support\ModuleNavigation::route(...));
        });

        View::composer('layouts.app', function (BladeView $view): void {
            $request = request();
            $user = $request->user();

            // Contador persistente de artículos en el carrito para el cliente autenticado
            $cantidadCarrito = 0;
            if ($user && $user->cliente) {
                $carrito = $user->cliente->carrito;
                if ($carrito) {
                    $cantidadCarrito = (int) $carrito->detalles()->sum('cantidad');
                }
            }
            $view->with('cantidadCarrito', $cantidadCarrito);

            if (! $user?->hasModuleAccess(User::MODULE_SCM)
                || ! in_array('auth', $request->route()?->gatherMiddleware() ?? [], true)) {
                return;
            }

            $productosBajos = Producto::query()
                ->select(['id', 'nombre', 'stock_actual', 'stock_minimo'])
                ->whereColumn('stock_actual', '<=', 'stock_minimo')
                ->orderBy('nombre')
                ->get();

            $sessionKey = "alertas_stock_bajo_{$user->id}";
            $idsActuales = $productosBajos->pluck('id')->all();
            $idsAvisados = array_intersect($request->session()->get($sessionKey, []), $idsActuales);
            $alertasStockBajo = $productosBajos->whereNotIn('id', $idsAvisados);

            // Guardar solo los productos que siguen bajos permite alertar tras recuperarse y volver a caer.
            $request->session()->put($sessionKey, $idsActuales);

            $view->with('alertasStockBajo', $alertasStockBajo)
                ->with('cantidadStockBajo', $productosBajos->count());
        });
    }
}
