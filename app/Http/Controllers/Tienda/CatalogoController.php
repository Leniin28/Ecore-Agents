<?php

namespace App\Http\Controllers\Tienda;

use App\Http\Controllers\Controller;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Promocion;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogoController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = $request->string('buscar')->trim()->toString();
        $categoriaId = $request->string('categoria_id')->trim()->toString();
        $soloDestacados = $request->boolean('destacados');
        $soloDisponibles = $request->boolean('disponibles');
        $orden = $request->string('orden', 'destacados')->toString();

        $query = Producto::query()
            ->with(['categoriaRelacion'])
            ->where('visible_tienda', true)
            ->when($buscar, function ($q) use ($buscar) {
                $q->where(function ($sub) use ($buscar) {
                    $sub->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('descripcion', 'like', "%{$buscar}%");
                });
            })
            ->when($categoriaId !== '', function ($q) use ($categoriaId) {
                if (ctype_digit($categoriaId)) {
                    $q->where('categoria_id', (int) $categoriaId);
                } else {
                    $q->where('categoria', $categoriaId);
                }
            })
            ->when($soloDestacados, fn ($q) => $q->where('destacado', true))
            ->when($soloDisponibles, fn ($q) => $q->where('stock_actual', '>', 0));

        match ($orden) {
            'precio_menor' => $query->orderBy('precio_venta_unitario', 'asc'),
            'precio_mayor' => $query->orderBy('precio_venta_unitario', 'desc'),
            'novedades' => $query->latest('id'),
            default => $query->orderByDesc('destacado')->orderBy('nombre', 'asc'),
        };

        $productos = $query->paginate(12)->withQueryString();
        $categorias = Categoria::query()->where('activo', true)->orderBy('nombre')->get();

        // Promociones vigentes para enriquecer las tarjetas de producto
        $promocionesVigentes = Promocion::query()
            ->where('activo', true)
            ->where('fecha_inicio', '<=', now())
            ->where('fecha_fin', '>=', now())
            ->with('productos')
            ->get();

        return view('tienda.catalogo', [
            'productos' => $productos,
            'categorias' => $categorias,
            'promocionesVigentes' => $promocionesVigentes,
        ]);
    }

    public function show(Producto $producto): View
    {
        abort_unless($producto->visible_tienda, 404);

        $producto->load(['categoriaRelacion', 'proveedor']);

        // Buscar promociones vigentes que apliquen a este producto
        $promocion = Promocion::query()
            ->where('activo', true)
            ->where('fecha_inicio', '<=', now())
            ->where('fecha_fin', '>=', now())
            ->get()
            ->first(fn ($p) => $p->aplicaAProducto($producto->id));

        $precioPromocionalCentavos = null;
        if ($promocion) {
            $descuento = $promocion->calcularDescuento($producto->precio_venta_centavos);
            $precioPromocionalCentavos = max(0, $producto->precio_venta_centavos - $descuento);
        }

        $relacionados = Producto::query()
            ->where('visible_tienda', true)
            ->where('id', '!=', $producto->id)
            ->where(function ($q) use ($producto) {
                if ($producto->categoria_id) {
                    $q->where('categoria_id', $producto->categoria_id);
                } else {
                    $q->where('categoria', $producto->categoria);
                }
            })
            ->limit(3)
            ->get();

        return view('tienda.detalle', [
            'producto' => $producto,
            'promocion' => $promocion,
            'precioPromocionalCentavos' => $precioPromocionalCentavos,
            'relacionados' => $relacionados,
        ]);
    }
}
