<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Services\MonederoService;
use App\Services\TiendaCarritoService;
use App\Services\TiendaCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        protected TiendaCarritoService $carritoService,
        protected TiendaCheckoutService $checkoutService,
        protected MonederoService $monederoService
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $usuario = $request->user();
        $carrito = $this->carritoService->obtenerCarrito($usuario);

        if ($carrito->detalles()->count() === 0) {
            return redirect()->route('carrito.index')->with('warning', 'Tu carrito está vacío. Agrega planes o complementos para continuar.');
        }

        $codigoPromocion = session('carrito_promocion');
        $totales = $this->carritoService->calcularTotales($carrito, $codigoPromocion);

        $cliente = $carrito->cliente;
        $monedero = $this->monederoService->obtenerMonedero($cliente);

        return view('tienda.checkout', [
            'carrito' => $carrito,
            'totales' => $totales,
            'cliente' => $cliente,
            'monedero' => $monedero,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nombre_contacto' => ['required', 'string', 'max:255'],
            'correo_contacto' => ['required', 'email', 'max:255'],
            'telefono_contacto' => ['required', 'string', 'max:50'],
            'empresa' => ['nullable', 'string', 'max:255'],
            'notas_servicio' => ['nullable', 'string', 'max:1000'],
            'metodo_pago' => ['required', 'string', 'in:'.implode(',', array_keys(Pago::METODOS))],
            'simular_fallo' => ['nullable', 'boolean'],
            'idempotency_key' => ['nullable', 'string', 'max:64'],
        ]);

        $datosEntrega = [
            'nombre_contacto' => $validated['nombre_contacto'],
            'correo_contacto' => $validated['correo_contacto'],
            'telefono_contacto' => $validated['telefono_contacto'],
            'empresa' => $validated['empresa'] ?? null,
            'notas_servicio' => $validated['notas_servicio'] ?? null,
        ];

        $codigoPromocion = session('carrito_promocion');
        $simularFallo = $request->boolean('simular_fallo');
        $idempotencyKey = $validated['idempotency_key'] ?? null;

        $resultado = $this->checkoutService->procesarCompra(
            $request->user(),
            $datosEntrega,
            $validated['metodo_pago'],
            $codigoPromocion,
            $idempotencyKey,
            $simularFallo
        );

        session()->forget('carrito_promocion');

        return redirect()->route('pagos.confirmacion', $resultado['pago']->referencia)
            ->with('success', '¡Compra simulada procesada exitosamente!');
    }

    public function confirmacion(Request $request, string $referencia): View
    {
        $pago = Pago::query()
            ->with(['ordenEmpresarial.detalles.producto', 'cliente', 'promocion'])
            ->where('referencia', $referencia)
            ->firstOrFail();

        $usuario = $request->user();

        // Control de acceso: el usuario debe ser el propietario de la compra o administrador
        if (! $usuario->isAdmin() && (! $usuario->cliente || $usuario->cliente->id !== $pago->cliente_id)) {
            abort(403, 'No tienes autorización para consultar este comprobante.');
        }

        return view('tienda.confirmacion', [
            'pago' => $pago,
            'orden' => $pago->ordenEmpresarial,
        ]);
    }
}
