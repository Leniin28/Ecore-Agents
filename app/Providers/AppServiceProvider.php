<?php

namespace App\Providers;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
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
        View::composer('layouts.app', function (BladeView $view): void {
            $request = request();
            $user = $request->user();

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
