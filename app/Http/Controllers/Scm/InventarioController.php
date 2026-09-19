<?php

namespace App\Http\Controllers\Scm;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventarioController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = $request->string('buscar')->trim()->toString();
        $productos = Producto::query()->with('proveedor')
            ->when($buscar, fn (Builder $query) => $query->where('nombre', 'like', "%{$buscar}%"))
            ->orderBy('nombre')->paginate(20)->withQueryString();

        return view('scm.inventario.index', compact('productos', 'buscar'));
    }
}
