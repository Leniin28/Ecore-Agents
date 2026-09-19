<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditoriaController extends Controller
{
    public function index(Request $request): View
    {
        $usuarioId = $request->integer('usuario_id');
        $accion = $request->string('accion')->trim()->toString();
        $entidad = $request->string('entidad')->trim()->toString();

        $auditorias = Auditoria::query()
            ->with('usuario')
            ->when($usuarioId, fn (Builder $query) => $query->where('usuario_id', $usuarioId))
            ->when($accion, fn (Builder $query) => $query->where('accion', $accion))
            ->when($entidad, fn (Builder $query) => $query->where('entidad', $entidad))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.auditoria.index', [
            'auditorias' => $auditorias,
            'usuarios' => User::query()->orderBy('name')->get(),
            'acciones' => Auditoria::query()->distinct()->orderBy('accion')->pluck('accion'),
            'entidades' => Auditoria::query()->distinct()->orderBy('entidad')->pluck('entidad'),
        ]);
    }
}
