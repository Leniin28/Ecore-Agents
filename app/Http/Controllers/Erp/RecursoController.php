<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecursoController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = $request->string('buscar')->trim()->toString();

        return view('erp.recursos', [
            'productos' => Producto::query()->with('proveedor')
                ->when($buscar, fn ($query) => $query->where('nombre', 'like', "%{$buscar}%"))
                ->orderBy('nombre')->paginate(20)->withQueryString(),
        ]);
    }
}
