<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use App\Services\TiendaCarritoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CarritoController extends Controller
{
    public function __construct(
        protected TiendaCarritoService $carritoService
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->route('login')->with('warning', 'Inicia sesión para consultar o gestionar tu carrito de compras.');
        }

        $carrito = $this->carritoService->obtenerCarrito($request->user());
        $codigoPromocion = session('carrito_promocion');

        $totales = $this->carritoService->calcularTotales($carrito, $codigoPromocion);

        if ($codigoPromocion && ! $totales['promocion_valida']) {
            session()->forget('carrito_promocion');
        }

        return view('tienda.carrito', [
            'carrito' => $carrito,
            'totales' => $totales,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! auth()->check()) {
            return redirect()->route('login')->with('warning', 'Inicia sesión para agregar productos a tu carrito.');
        }

        $validated = $request->validate([
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'cantidad' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $this->carritoService->agregarProducto(
            $request->user(),
            (int) $validated['producto_id'],
            (int) $validated['cantidad']
        );

        return redirect()->route('carrito.index')->with('success', 'Producto agregado a tu carrito.');
    }

    public function update(Request $request, int $detalle): RedirectResponse
    {
        $validated = $request->validate([
            'cantidad' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $this->carritoService->actualizarCantidad(
            $request->user(),
            $detalle,
            (int) $validated['cantidad']
        );

        return redirect()->route('carrito.index')->with('success', 'Cantidad actualizada correctamente.');
    }

    public function destroy(Request $request, int $detalle): RedirectResponse
    {
        $this->carritoService->eliminarDetalle($request->user(), $detalle);

        return redirect()->route('carrito.index')->with('success', 'Producto eliminado de tu carrito.');
    }

    public function vaciar(Request $request): RedirectResponse
    {
        $this->carritoService->vaciar($request->user());
        session()->forget('carrito_promocion');

        return redirect()->route('carrito.index')->with('success', 'Carrito vaciado correctamente.');
    }

    public function aplicarPromocion(Request $request): RedirectResponse
    {
        $request->validate([
            'codigo' => ['required', 'string', 'max:50'],
        ]);

        $codigo = strtoupper(trim($request->input('codigo')));
        $carrito = $this->carritoService->obtenerCarrito($request->user());
        $totales = $this->carritoService->calcularTotales($carrito, $codigo);

        if (! $totales['promocion_valida']) {
            return redirect()->route('carrito.index')->withErrors([
                'codigo' => $totales['mensaje_promocion'] ?? 'El código de promoción no es válido.',
            ]);
        }

        session(['carrito_promocion' => $codigo]);

        return redirect()->route('carrito.index')->with('success', $totales['mensaje_promocion']);
    }

    public function quitarPromocion(): RedirectResponse
    {
        session()->forget('carrito_promocion');

        return redirect()->route('carrito.index')->with('success', 'Promoción eliminada del carrito.');
    }
}
