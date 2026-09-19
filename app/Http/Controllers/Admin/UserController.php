<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('admin.usuarios.index', [
            'usuarios' => User::query()->with('cliente')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.usuarios.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->createRules(), $this->messages());
        $permissions = $this->permissionsForRole($validated['role'], $validated['permissions'] ?? []);

        $usuario = DB::transaction(function () use ($request, $validated, $permissions): User {
            $usuario = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => $validated['role'],
                'permissions' => $permissions,
            ]);

            Auditoria::registrar(
                $request->user(),
                'crear',
                'usuario',
                $usuario->id,
                "Creó al {$usuario->roleLabel()} {$usuario->name}.",
            );

            return $usuario;
        });

        return redirect()->route('admin.usuarios.index')
            ->with('success', "Usuario {$usuario->name} creado correctamente.");
    }

    public function edit(User $usuario): View
    {
        abort_if($usuario->isClient(), 403, 'Las cuentas de clientes no se convierten desde esta pantalla.');

        return view('admin.usuarios.edit', compact('usuario'));
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        abort_if($usuario->isClient(), 403, 'Las cuentas de clientes no se convierten desde esta pantalla.');

        $validated = $request->validate($this->updateRules(), $this->messages());

        DB::transaction(function () use ($request, $usuario, $validated): void {
            $usuario = User::query()->lockForUpdate()->findOrFail($usuario->id);

            if ($usuario->isAdmin()
                && $validated['role'] !== User::ROLE_ADMIN
                && User::query()->where('role', User::ROLE_ADMIN)->count() <= 1) {
                throw ValidationException::withMessages([
                    'role' => 'No se puede cambiar el rol del último administrador.',
                ]);
            }

            $oldRole = $usuario->role;
            $oldPermissions = $usuario->permissions ?? [];
            $newPermissions = $this->permissionsForRole($validated['role'], $validated['permissions'] ?? []);

            $usuario->update([
                'name' => $validated['name'],
                'role' => $validated['role'],
                'permissions' => $newPermissions,
            ]);

            if ($oldRole !== $usuario->role) {
                Auditoria::registrar($request->user(), 'cambiar_rol', 'usuario', $usuario->id,
                    "Cambió el rol de {$usuario->name} de {$oldRole} a {$usuario->role}.");
            }

            if ($oldPermissions !== ($usuario->permissions ?? [])) {
                Auditoria::registrar($request->user(), 'cambiar_permisos', 'usuario', $usuario->id,
                    "Actualizó los permisos de módulos de {$usuario->name}.");
            }
        });

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function editPassword(User $usuario): View
    {
        return view('admin.usuarios.password', compact('usuario'));
    }

    public function updatePassword(Request $request, User $usuario): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ], $this->messages());

        DB::transaction(function () use ($request, $usuario, $validated): void {
            $usuario->update(['password' => $validated['password']]);
            Auditoria::registrar($request->user(), 'restablecer_password', 'usuario', $usuario->id,
                "Restableció la contraseña del usuario {$usuario->name}.");
        });

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'Contraseña restablecida correctamente.');
    }

    /** @return array<string, mixed> */
    private function createRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', Rule::in([User::ROLE_EMPLEADO, User::ROLE_ADMIN])],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(User::MODULES)],
        ];
    }

    /** @return array<string, mixed> */
    private function updateRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in([User::ROLE_EMPLEADO, User::ROLE_ADMIN])],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(User::MODULES)],
        ];
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'El correo debe tener un formato válido.',
            'email.unique' => 'Ya existe un usuario con este correo.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'password.min' => 'La contraseña debe tener al menos :min caracteres.',
            'role.required' => 'El rol es obligatorio.',
            'role.in' => 'El rol seleccionado no es válido.',
            'permissions.array' => 'Los permisos deben ser una lista válida.',
            'permissions.*.in' => 'El permiso seleccionado no es válido.',
        ];
    }

    /** @param array<int, string> $permissions
     *  @return array<int, string>|null
     */
    private function permissionsForRole(string $role, array $permissions): ?array
    {
        if ($role === User::ROLE_ADMIN) {
            return null;
        }

        return array_values(array_unique(array_intersect(User::MODULES, $permissions)));
    }
}
